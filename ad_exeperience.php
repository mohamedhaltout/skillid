<?php
require 'config.php';
session_start();

if (!isset($_SESSION['artisan_id']) || $_SESSION['role'] !== 'prestataire') {
    header("Location: login.php");
    exit();
}

// Get the id_prestataire
$stmt = $pdo->prepare("SELECT id_prestataire FROM Prestataire WHERE id_utilisateur = ?");
$stmt->execute([$_SESSION['id_utilisateur']]);
$prestataire = $stmt->fetch();

if (!$prestataire) {
    die("No prestataire found.");
}

$id_prestataire = $prestataire['id_prestataire'];
$message = "";
$experience_data = null;
$existing_media = [];
$is_edit_mode = false;

if (isset($_GET['edit_id'])) {
    $is_edit_mode = true;
    $edit_id = $_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM Experience_prestataire WHERE id_experience = ? AND id_prestataire = ?");
    $stmt->execute([$edit_id, $id_prestataire]);
    $experience_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$experience_data) {
        die("Experience not found or you don't have permission to edit it.");
    }

    // Fetch existing media for this experience
    $stmt = $pdo->prepare("SELECT id_media, chemin_fichier, type_contenu FROM Media_experience WHERE id_experience = ?");
    $stmt->execute([$edit_id]);
    $existing_media = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (isset($_POST['titre_experience'], $_POST['description'], $_POST['date_project'])) {
    $titre = $_POST['titre_experience'];
    $description = $_POST['description'];
    $annee = $_POST['date_project'];
    $id_experience_to_update = isset($_POST['id_experience']) ? $_POST['id_experience'] : null;

    if (empty($titre) || empty($description) || empty($annee)) {
        $message = "All fields are required.";
    } else {
        if ($is_edit_mode && $id_experience_to_update) {
            // Update experience
            $stmt = $pdo->prepare("UPDATE Experience_prestataire SET titre_experience = ?, description = ?, date_project = ? WHERE id_experience = ? AND id_prestataire = ?");
            $stmt->execute([$titre, $description, $annee, $id_experience_to_update, $id_prestataire]);
            $id_experience = $id_experience_to_update; // Use the existing ID for media handling
            $message = "Experience updated successfully!";

            // Handle media deletion
            if (isset($_POST['delete_media_ids'])) {
                $delete_ids = explode(',', $_POST['delete_media_ids']);
                foreach ($delete_ids as $media_id) {
                    if (!empty($media_id)) {
                        // Get file path to delete from server
                        $stmt = $pdo->prepare("SELECT chemin_fichier FROM Media_experience WHERE id_media = ? AND id_experience = ?");
                        $stmt->execute([$media_id, $id_experience]);
                        $file_to_delete = $stmt->fetchColumn();

                        if ($file_to_delete && file_exists($file_to_delete)) {
                            unlink($file_to_delete); // Delete file from server
                        }

                        $stmt = $pdo->prepare("DELETE FROM Media_experience WHERE id_media = ? AND id_experience = ?");
                        $stmt->execute([$media_id, $id_experience]);
                    }
                }
            }

        } else {
            // Insert new experience
            $stmt = $pdo->prepare("INSERT INTO Experience_prestataire (id_prestataire, titre_experience, description, date_project) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_prestataire, $titre, $description, $annee]);
            $id_experience = $pdo->lastInsertId();
            $message = "Experience added successfully!";
        }

        // Handle new file uploads (for both add and edit)
        if (!empty($_FILES['media_files']['name'][0])) {
            $uploadDir = "uploads/media/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            foreach ($_FILES['media_files']['tmp_name'] as $index => $tmpName) {
                if ($_FILES['media_files']['error'][$index] === UPLOAD_ERR_OK) {
                    $originalName = $_FILES['media_files']['name'][$index];
                    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                    $type = in_array($ext, ['mp4', 'mov', 'avi']) ? 'video' : 'image';
                    $newName = uniqid() . '_' . basename($originalName);
                    $destPath = $uploadDir . $newName;

                    move_uploaded_file($tmpName, $destPath);

                    $stmt = $pdo->prepare("INSERT INTO Media_experience (id_experience, type_contenu, chemin_fichier) VALUES (?, ?, ?)");
                    $stmt->execute([$id_experience, $type, $destPath]);
                }
            }
        }

        header("Location: artisan.php?id=" . $id_prestataire);
        exit;
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Work Experience</title>
    <link rel="stylesheet" href="ad_exeperience.css">
    <style>
        .media-upload-button.preview-added {
            border-color: #3E185B;
            background-color: #EDEDED;
        }
        .media-upload-button img.preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="left-decoration"></div>
        <div class="right-content">
            <div class="title"><?php echo $is_edit_mode ? 'Edit Work Experience' : 'Add Work Experience'; ?></div>

            <?php if (!empty($message)): ?>
                <p style="color: green; font-weight: bold; text-align:center;"> <?= htmlspecialchars($message) ?> </p>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <?php if ($is_edit_mode): ?>
                    <input type="hidden" name="id_experience" value="<?php echo htmlspecialchars($experience_data['id_experience']); ?>">
                    <input type="hidden" name="delete_media_ids" id="delete-media-ids" value="">
                <?php endif; ?>

                <div class="form-group">
                    <div class="form-info">
                        <label for="experience-title-input">Experience Title</label>
                        <div class="description">e.g., Full villa painting 2023</div>
                    </div>
                    <div class="form-input">
                        <input type="text" name="titre_experience" id="experience-title-input" placeholder="Experience Title" value="<?php echo $is_edit_mode ? htmlspecialchars($experience_data['titre_experience']) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-info">
                        <label for="detailed-description-textarea">Detailed Description</label>
                        <div class="description">Describe what you did, techniques used, and time taken...</div>
                    </div>
                    <div class="form-input">
                        <textarea name="description" id="detailed-description-textarea" placeholder="Your description here..." required><?php echo $is_edit_mode ? htmlspecialchars($experience_data['description']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-info">
                        <label for="project-year-input">Enter the year</label>
                        <div class="description">(e.g., 2023)</div>
                    </div>
                    <div class="form-input">
                        <input type="text" name="date_project" id="project-year-input" placeholder="2023" value="<?php echo $is_edit_mode ? htmlspecialchars($experience_data['date_project']) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-group upload-group">
                    <div class="form-info">
                        <label>Upload photos or videos</label>
                        <div class="description">Showcase your work</div>
                    </div>
                    <div class="form-input media-upload-container">
                        <?php
                        $media_count = count($existing_media);
                        for ($i = 0; $i < 5; $i++):
                            $media_item = isset($existing_media[$i]) ? $existing_media[$i] : null;
                            $button_id = "button-" . ($i + 1);
                            $input_id = "media-upload-" . ($i + 1);
                            $is_filled = $media_item ? 'preview-added' : '';
                            $media_src = $media_item ? htmlspecialchars($media_item['chemin_fichier']) : 'img/add_media.svg';
                            $media_type = $media_item ? htmlspecialchars($media_item['type_contenu']) : '';
                            $media_id = $media_item ? htmlspecialchars($media_item['id_media']) : '';
                        ?>
                            <label for="<?= $input_id ?>" class="media-upload-button <?= $is_filled ?>" id="<?= $button_id ?>" data-media-id="<?= $media_id ?>">
                                <input type="file" name="media_files[]" id="<?= $input_id ?>" accept="image/*,video/*" hidden onchange="previewMedia(this, <?= $i + 1 ?>)">
                                <?php if ($media_item): ?>
                                    <?php if ($media_type === 'image'): ?>
                                        <img src="<?= $media_src ?>" alt="Media Preview" class="preview">
                                    <?php elseif ($media_type === 'video'): ?>
                                        <video src="<?= $media_src ?>" controls class="preview"></video>
                                    <?php endif; ?>
                                    <button type="button" class="delete-media-button" onclick="deleteMedia(event, this, '<?= $media_id ?>')">X</button>
                                <?php else: ?>
                                    <img src="img/add_media.svg" alt="Add Icon" class="add-icon">
                                <?php endif; ?>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="divider"></div>

                <button type="submit" class="submit-button">Complete</button>

                <div class="alert-box">
                    <img src="img/avertisement.svg" alt="Alert Icon">
                    <span>Please do not make any transactions, payments, or<br> contact outside of Skillid.</span>
                </div>
            </form>
        </div>
    </div>

    <script>
        let deletedMediaIds = [];

        function previewMedia(input, index) {
            const button = document.getElementById(`button-${index}`);
            const existingPreview = button.querySelector('.preview');
            const existingAddIcon = button.querySelector('.add-icon');
            const existingDeleteButton = button.querySelector('.delete-media-button');

            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();

                reader.onload = function(e) {
                    if (existingPreview) {
                        existingPreview.remove();
                    }
                    if (existingAddIcon) {
                        existingAddIcon.remove();
                    }
                    if (existingDeleteButton) {
                        existingDeleteButton.remove();
                    }

                    let mediaElement;
                    if (file.type.startsWith('image/')) {
                        mediaElement = document.createElement('img');
                        mediaElement.src = e.target.result;
                        mediaElement.alt = "Media Preview";
                        mediaElement.classList.add('preview');
                    } else if (file.type.startsWith('video/')) {
                        mediaElement = document.createElement('video');
                        mediaElement.src = e.target.result;
                        mediaElement.controls = true;
                        mediaElement.classList.add('preview');
                    }

                    if (mediaElement) {
                        button.prepend(mediaElement);
                        button.classList.add('preview-added');

                        const deleteButton = document.createElement('button');
                        deleteButton.type = 'button';
                        deleteButton.classList.add('delete-media-button');
                        deleteButton.textContent = 'X';
                        deleteButton.onclick = (event) => deleteMedia(event, deleteButton, null, input); // Pass input for new files
                        button.appendChild(deleteButton);
                    }
                };
                reader.readAsDataURL(file);
            } else {
                // If file is cleared, revert to add icon
                if (existingPreview) {
                    existingPreview.remove();
                }
                if (existingDeleteButton) {
                    existingDeleteButton.remove();
                }
                if (!existingAddIcon) {
                    const addIcon = document.createElement('img');
                    addIcon.src = "img/add_media.svg";
                    addIcon.alt = "Add Icon";
                    addIcon.classList.add('add-icon');
                    parentLabel.prepend(addIcon);
                }
                button.classList.remove('preview-added');
            }
        }

        function deleteMedia(event, buttonElement, mediaId, fileInput = null) {
            event.stopPropagation(); // Prevent the label from re-triggering file input

            const parentLabel = buttonElement.closest('.media-upload-button');
            if (mediaId) {
                // This is an existing media file from the database
                deletedMediaIds.push(mediaId);
                document.getElementById('delete-media-ids').value = deletedMediaIds.join(',');
            }

            // Clear the file input if it's a newly selected file
            if (fileInput) {
                fileInput.value = ''; // Clear the selected file
            }

            // Remove the preview and delete button
            const preview = parentLabel.querySelector('.preview');
            if (preview) {
                preview.remove();
            }
            buttonElement.remove();

            // Add back the default add icon
            const addIcon = document.createElement('img');
            addIcon.src = "img/add_media.svg";
            addIcon.alt = "Add Icon";
            addIcon.classList.add('add-icon');
            parentLabel.prepend(addIcon);
            parentLabel.classList.remove('preview-added');
            parentLabel.removeAttribute('data-media-id'); // Remove data-media-id for cleared slots
        }
    </script>
</body>
</html>
