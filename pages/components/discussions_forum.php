<?php   

if ($action == 'view' && $topic_id > 0): 
    try {
        global $pdo;
        $db = $pdo; // Utiliser la connexion PDO existante
        if (!$db) {
            die("Erreur de connexion à la base de données.");
        }

        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Gestion de la suppression d'une réponse
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reply'])) {
            $reply_id = $_POST['reply_id'];
            $user_id = $_SESSION['user_id'];

            $stmt = $db->prepare("DELETE FROM forum_replies WHERE id = ? AND user_id = ?");
            $stmt->execute([$reply_id, $user_id]);

            // Rediriger pour éviter la soumission multiple du formulaire
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }

        // Gestion de la modification d'une réponse
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_reply'])) {
            $reply_id = $_POST['reply_id'];
            $reply_content = trim($_POST['reply_content']);
            $user_id = $_SESSION['user_id'];

            $stmt = $db->prepare("UPDATE forum_replies SET content = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
            $stmt->execute([$reply_content, $reply_id, $user_id]);

            // Rediriger pour éviter la soumission multiple du formulaire
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }

        // Mise à jour du compteur de vues
        $update_views = $db->prepare("UPDATE forum_topics SET views = views + 1 WHERE id = ?");
        $update_views->execute([$topic_id]);

        // Récupération des détails de la discussion et de l'auteur
        $stmt = $db->prepare("SELECT t.*, u.name, u.avatar 
                               FROM forum_topics t 
                               JOIN users u ON t.user_id = u.id 
                               WHERE t.id = ?");
        $stmt->execute([$topic_id]);
        $topic = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($topic) {
            $created_date = new DateTime($topic['created_at']);
            $formatted_date = $created_date->format('d/m/Y à H:i');

            $category_class = '';
            $category_name = '';

            switch ($topic['category']) {
                case 'procedure':
                    $category_class = 'bg-blue-100 text-blue-800';
                    $category_name = 'Procédure Campus France';
                    break;
                case 'visa':
                    $category_class = 'bg-green-100 text-green-800';
                    $category_name = 'Visa étudiant';
                    break;
                case 'logement':
                    $category_class = 'bg-yellow-100 text-yellow-800';
                    $category_name = 'Logement';
                    break;
                case 'etudes':
                    $category_class = 'bg-purple-100 text-purple-800';
                    $category_name = 'Études et formations';
                    break;
                case 'vie':
                    $category_class = 'bg-pink-100 text-pink-800';
                    $category_name = 'Vie en France';
                    break;
                default:
                    $category_class = 'bg-gray-100 text-gray-800';
                    $category_name = 'Autres';
                    break;
            }
            ?>
            <div class="p-6 bg-white rounded-lg shadow-md">
                <h2 class="text-2xl font-semibold text-gray-900"><?php echo htmlspecialchars($topic['title']); ?></h2>
                <div class="flex items-center space-x-4 text-sm text-gray-600 mt-2">
                    <span class="px-3 py-1 rounded-full <?php echo $category_class; ?>"><?php echo $category_name; ?></span>
                    <span>Posté par <strong><?php echo htmlspecialchars($topic['name']); ?></strong></span>
                    <span><?php echo $formatted_date; ?></span>
                </div>
                <div class="mt-4 text-gray-700">
                    <p><?php echo nl2br(htmlspecialchars($topic['content'])); ?></p>
                </div>
                <div class="mt-6">
                    <button id="reply-btn" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"><i class="fas fa-reply"></i> Répondre</button>
                </div>

                <?php
                // Récupération des réponses
                $stmt_replies = $db->prepare("SELECT r.*, u.name FROM forum_replies r JOIN users u ON r.user_id = u.id WHERE r.topic_id = ? ORDER BY r.created_at ASC");
                $stmt_replies->execute([$topic_id]);
                $replies = $stmt_replies->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <div class="mt-6">
                    <h3 class="text-lg font-semibold">Réponses</h3>
                    <?php foreach ($replies as $reply): 
                        $reply_date = new DateTime($reply['created_at']);
                        $formatted_reply_date = $reply_date->format('d/m/Y à H:i');
                    ?>
                        <div class="p-4 mt-2 bg-gray-100 rounded-lg shadow-sm">
                            <p><strong><?php echo htmlspecialchars($reply['name']); ?></strong> <span class="text-sm text-gray-500"><?php echo $formatted_reply_date; ?></span></p>
                            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                            <?php if ($_SESSION['user_id'] == $reply['user_id']): ?>
                                <div class="mt-2 flex space-x-2">
                                    <button onclick="openEditModal(<?php echo $reply['id']; ?>, '<?php echo addslashes($reply['content']); ?>')" class="px-2 py-1 bg-yellow-500 text-white rounded">Modifier</button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="reply_id" value="<?php echo $reply['id']; ?>">
                                        <button type="submit" name="delete_reply" class="px-2 py-1 bg-red-500 text-white rounded">Supprimer</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Popup pour répondre -->
                <div id="reply-popup" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
                    <div class="relative bg-white p-6 rounded-lg shadow-lg w-96">
                        <button id="close-popup" class="absolute top-2 right-2 text-gray-600 hover:text-gray-900">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                        <h3 class="text-lg font-semibold mb-4">Répondre à la discussion</h3>
                        <form method="POST">
                            <input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
                            <textarea name="reply_content" required placeholder="Votre réponse..." class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            <button type="submit" name="submit_reply" class="mt-3 w-full px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">Envoyer</button>
                        </form>
                    </div>
                </div>

                <!-- Popup pour modifier une réponse -->
                <div id="edit-popup" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
                    <div class="relative bg-white p-6 rounded-lg shadow-lg w-96">
                        <button id="close-edit-popup" class="absolute top-2 right-2 text-gray-600 hover:text-gray-900">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                        <h3 class="text-lg font-semibold mb-4">Modifier la réponse</h3>
                        <form method="POST">
                            <input type="hidden" name="reply_id" id="edit-reply-id">
                            <textarea name="reply_content" id="edit-reply-content" required placeholder="Modifier votre réponse..." class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            <button type="submit" name="edit_reply" class="mt-3 w-full px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">Modifier</button>
                        </form>
                    </div>
                </div>
            </div>

            <script>
                // Gestion de la popup de réponse
                document.getElementById('reply-btn').addEventListener('click', function() {
                    document.getElementById('reply-popup').classList.remove('hidden');
                });
                document.getElementById('close-popup').addEventListener('click', function() {
                    document.getElementById('reply-popup').classList.add('hidden');
                });

                // Gestion de la popup de modification
                function openEditModal(replyId, content) {
                    document.getElementById('edit-reply-id').value = replyId;
                    document.getElementById('edit-reply-content').value = content;
                    document.getElementById('edit-popup').classList.remove('hidden');
                }
                document.getElementById('close-edit-popup').addEventListener('click', function() {
                    document.getElementById('edit-popup').classList.add('hidden');
                });
            </script>

            <?php
            // Gestion de l'ajout d'une réponse
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply'])) {
                $reply_content = trim($_POST['reply_content']);
                $user_id = $_SESSION['user_id'] ?? 1;

                if (!empty($reply_content)) {
                    $insert_reply = $db->prepare("INSERT INTO forum_replies (topic_id, user_id, content, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                    $insert_reply->execute([$topic_id, $user_id, $reply_content]);

                    // Rediriger pour éviter la soumission multiple du formulaire
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit();
                }
            }
        }
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
endif;