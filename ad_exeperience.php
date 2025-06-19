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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle single experience edit submission
    if (isset($_POST['id_experience'])) {
        $titre = $_POST['experiences'][0]['titre_experience'] ?? ''; // Assuming edit mode uses index 0
        $description = $_POST['experiences'][0]['description'] ?? '';
        $annee = $_POST['experiences'][0]['date_project'] ?? '';
        $id_experience_to_update = $_POST['id_experience'];

        if (empty($titre) || empty($description) || empty($annee)) {
            $message = "All fields are required for editing.";
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE Experience_prestataire SET titre_experience = ?, description = ?, date_project = ? WHERE id_experience = ? AND id_prestataire = ?");
                $stmt->execute([$titre, $description, $annee, $id_experience_to_update, $id_prestataire]);
                $message = "Experience updated successfully!";

                // Handle media deletion for single edit
                if (isset($_POST['delete_media_ids'])) {
                    $delete_ids = explode(',', $_POST['delete_media_ids']);
                    foreach ($delete_ids as $media_id) {
                        if (!empty($media_id)) {
                            $stmt = $pdo->prepare("SELECT chemin_fichier FROM Media_experience WHERE id_media = ? AND id_experience = ?");
                            $stmt->execute([$media_id, $id_experience_to_update]);
                            $file_to_delete = $stmt->fetchColumn();

                            if ($file_to_delete && file_exists($file_to_delete)) {
                                unlink($file_to_delete);
                            }
                            $stmt = $pdo->prepare("DELETE FROM Media_experience WHERE id_media = ? AND id_experience = ?");
                            $stmt->execute([$media_id, $id_experience_to_update]);
                        }
                    }
                }

                // Handle new file uploads for single edit (assuming media_files_0[] for the edit form)
                if (isset($_FILES['media_files_0']) && !empty($_FILES['media_files_0']['name'][0])) {
                    $uploadDir = "uploads/media/";
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                    foreach ($_FILES['media_files_0']['tmp_name'] as $file_idx => $tmpName) {
                        if ($_FILES['media_files_0']['error'][$file_idx] === UPLOAD_ERR_OK) {
                            $originalName = $_FILES['media_files_0']['name'][$file_idx];
                            $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                            $type = in_array($ext, ['mp4', 'mov', 'avi']) ? 'video' : 'image';
                            $newName = uniqid() . '_' . basename($originalName);
                            $destPath = $uploadDir . $newName;

                            move_uploaded_file($tmpName, $destPath);

                            $stmt = $pdo->prepare("INSERT INTO Media_experience (id_experience, type_contenu, chemin_fichier) VALUES (?, ?, ?)");
                            $stmt->execute([$id_experience_to_update, $type, $destPath]);
                        }
                    }
                }
                $pdo->commit();
                header("Location: artisan.php?id=" . $id_prestataire);
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = "Error updating experience: " . $e->getMessage();
            }
        }
    }
    // Handle multiple new experiences submission
    else if (isset($_POST['experiences']) && is_array($_POST['experiences'])) {
        $all_experiences_added = true;
        foreach ($_POST['experiences'] as $index => $experience) {
            $titre = $experience['titre_experience'] ?? '';
            $description = $experience['description'] ?? '';
            $annee = $experience['date_project'] ?? '';

            if (empty($titre) || empty($description) || empty($annee)) {
                $message = "All fields are required for each experience.";
                $all_experiences_added = false;
                break; // Stop processing if any experience is incomplete
            }

            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO Experience_prestataire (id_prestataire, titre_experience, description, date_project) VALUES (?, ?, ?, ?)");
                $stmt->execute([$id_prestataire, $titre, $description, $annee]);
                $id_experience = $pdo->lastInsertId();

                // Handle media uploads for this specific experience block
                $media_files_key = 'media_files_' . $index;
                if (isset($_FILES[$media_files_key]) && !empty($_FILES[$media_files_key]['name'][0])) {
                    $uploadDir = "uploads/media/";
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                    foreach ($_FILES[$media_files_key]['tmp_name'] as $file_idx => $tmpName) {
                        if ($_FILES[$media_files_key]['error'][$file_idx] === UPLOAD_ERR_OK) {
                            $originalName = $_FILES[$media_files_key]['name'][$file_idx];
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
                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = "Error adding experience: " . $e->getMessage();
                $all_experiences_added = false;
                break;
            }
        }

        if ($all_experiences_added) {
            $message = "All experiences added successfully!";
            header("Location: artisan.php?id=" . $id_prestataire);
            exit;
        }
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

                <div id="experience-forms-container">
                    <?php if ($is_edit_mode): ?>
                        <!-- Existing experience form for edit mode -->
                        <div class="experience-form-block" data-experience-id="<?php echo htmlspecialchars($experience_data['id_experience']); ?>">
                            <div class="form-group">
                                <div class="form-info">
                                    <label for="experience-title-input-0">Experience Title</label>
                                    <div class="description">e.g., Full villa painting 2023</div>
                                </div>
                                <div class="form-input">
                                    <input type="text" name="experiences[0][titre_experience]" id="experience-title-input-0" placeholder="Experience Title" value="<?php echo htmlspecialchars($experience_data['titre_experience']); ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="form-info">
                                    <label for="detailed-description-textarea-0">Detailed Description</label>
                                    <div class="description">Describe what you did, techniques used, and time taken...</div>
                                </div>
                                <div class="form-input">
                                    <textarea name="experiences[0][description]" id="detailed-description-textarea-0" placeholder="Your description here..." required><?php echo htmlspecialchars($experience_data['description']); ?></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="form-info">
                                    <label for="project-year-input-0">Enter the year</label>
                                    <div class="description">(e.g., 2023)</div>
                                </div>
                                <div class="form-input">
                                    <input type="text" name="experiences[0][date_project]" id="project-year-input-0" placeholder="2023" value="<?php echo htmlspecialchars($experience_data['date_project']); ?>" required>
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
                                        $button_id = "button-0-" . ($i + 1); // Unique ID for edit mode
                                        $input_id = "media-upload-0-" . ($i + 1); // Unique ID for edit mode
                                        $is_filled = $media_item ? 'preview-added' : '';
                                        $media_src = $media_item ? htmlspecialchars($media_item['chemin_fichier']) : 'img/add_media.svg';
                                        $media_type = $media_item ? htmlspecialchars($media_item['type_contenu']) : '';
                                        $media_id = $media_item ? htmlspecialchars($media_item['id_media']) : '';
                                    ?>
                                        <label for="<?= $input_id ?>" class="media-upload-button <?= $is_filled ?>" id="<?= $button_id ?>" data-media-id="<?= $media_id ?>">
                                            <input type="file" name="media_files_0[]" id="<?= $input_id ?>" accept="image/*,video/*" hidden onchange="previewMedia(this, '0-<?= $i + 1 ?>')">
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
                        </div>
                    <?php else: ?>
                        <!-- Initial empty form for add mode -->
                        <div class="experience-form-block" data-experience-id="">
                            <div class="form-group">
                                <div class="form-info">
                                    <label for="experience-title-input-0">Experience Title</label>
                                    <div class="description">e.g., Full villa painting 2023</div>
                                </div>
                                <div class="form-input">
                                    <input type="text" name="experiences[0][titre_experience]" id="experience-title-input-0" placeholder="Experience Title" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="form-info">
                                    <label for="detailed-description-textarea-0">Detailed Description</label>
                                    <div class="description">Describe what you did, techniques used, and time taken...</div>
                                </div>
                                <div class="form-input">
                                    <textarea name="experiences[0][description]" id="detailed-description-textarea-0" placeholder="Your description here..." required></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="form-info">
                                    <label for="project-year-input-0">Enter the year</label>
                                    <div class="description">(e.g., 2023)</div>
                                </div>
                                <div class="form-input">
                                    <input type="text" name="experiences[0][date_project]" id="project-year-input-0" placeholder="2023" required>
                                </div>
                            </div>

                            <div class="form-group upload-group">
                                <div class="form-info">
                                    <label>Upload photos or videos</label>
                                    <div class="description">Showcase your work</div>
                                </div>
                                <div class="form-input media-upload-container">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <label for="media-upload-0-<?= $i + 1 ?>" class="media-upload-button" id="button-0-<?= $i + 1 ?>">
                                            <input type="file" name="media_files_0[]" id="media-upload-0-<?= $i + 1 ?>" accept="image/*,video/*" hidden onchange="previewMedia(this, '0-<?= $i + 1 ?>')">
                                            <img src="img/add_media.svg" alt="Add Icon" class="add-icon">
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="divider"></div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!$is_edit_mode): ?>
                    <button type="button" id="add-more-experience" class="add-more-button">Add More Experience</button>
                <?php endif; ?>

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
        let experienceCounter = <?php echo $is_edit_mode ? 0 : 0; ?>; // Start at 0 for add mode, 0 for edit mode (as it's a single form)

        function previewMedia(input, index) {
            const button = document.getElementById(`button-${index}`);
            if (!button) {
                console.error(`Button with ID button-${index} not found.`);
                return;
            }
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
                        deleteButton.onclick = (event) => deleteMedia(event, deleteButton, null, input);
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
                // Ensure the add icon is present if no file is selected
                if (!existingAddIcon) {
                    const addIcon = document.createElement('img');
                    addIcon.src = "img/add_media.svg";
                    addIcon.alt = "Add Icon";
                    addIcon.classList.add('add-icon');
                    button.prepend(addIcon);
                }
                button.classList.remove('preview-added');
                button.removeAttribute('data-media-id');
            }
        }

        function deleteMedia(event, buttonElement, mediaId, fileInput = null) {
            event.stopPropagation();

            const parentLabel = buttonElement.closest('.media-upload-button');
            if (mediaId) {
                deletedMediaIds.push(mediaId);
                const deleteMediaIdsInput = document.getElementById('delete-media-ids');
                if (deleteMediaIdsInput) {
                    deleteMediaIdsInput.value = deletedMediaIds.join(',');
                }
            }

            if (fileInput) {
                fileInput.value = '';
            }

            const preview = parentLabel.querySelector('.preview');
            if (preview) {
                preview.remove();
            }
            buttonElement.remove();

            const addIcon = document.createElement('img');
            addIcon.src = "img/add_media.svg";
            addIcon.alt = "Add Icon";
            addIcon.classList.add('add-icon');
            parentLabel.prepend(addIcon);
            parentLabel.classList.remove('preview-added');
            parentLabel.removeAttribute('data-media-id');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const addMoreButton = document.getElementById('add-more-experience');
            const experienceFormsContainer = document.getElementById('experience-forms-container');

            if (addMoreButton) {
                addMoreButton.addEventListener('click', function() {
                    experienceCounter++;
                    const originalBlock = document.querySelector('.experience-form-block');
                    const clonedBlock = originalBlock.cloneNode(true);

                    // Reset values and update IDs/names for the cloned block
                    clonedBlock.setAttribute('data-experience-id', ''); // New blocks don't have an ID yet

                    clonedBlock.querySelectorAll('[id]').forEach(element => {
                        const oldId = element.id;
                        const newId = oldId.replace(/-\d+$/, `-${experienceCounter}`);
                        element.id = newId;
                        if (element.tagName === 'LABEL') {
                            element.setAttribute('for', newId);
                        }
                    });

                    clonedBlock.querySelectorAll('[name]').forEach(element => {
                        const oldName = element.name;
                        const newName = oldName.replace(/\[\d+\]/, `[${experienceCounter}]`);
                        element.name = newName;
                    });

                    // Clear input values and reset media previews
                    clonedBlock.querySelectorAll('input[type="text"], textarea').forEach(input => {
                        input.value = '';
                    });

                    clonedBlock.querySelectorAll('input[type="file"]').forEach(input => {
                        input.value = ''; // Clear selected files
                        const oldName = input.name;
                        const newName = oldName.replace(/_(\d+)\[\]/, `_${experienceCounter}[]`);
                        input.name = newName;
                        input.onchange = function() {
                            previewMedia(this, `${experienceCounter}-${input.id.split('-').pop()}`);
                        };
                    });

                    clonedBlock.querySelectorAll('.media-upload-button').forEach((button, i) => {
                        button.classList.remove('preview-added');
                        button.removeAttribute('data-media-id');
                        button.id = `button-${experienceCounter}-${i + 1}`; // Update button ID

                        const preview = button.querySelector('.preview');
                        if (preview) {
                            preview.remove();
                        }
                        const deleteBtn = button.querySelector('.delete-media-button');
                        if (deleteBtn) {
                            deleteBtn.remove();
                        }
                        let addIcon = button.querySelector('.add-icon');
                        if (!addIcon) {
                            addIcon = document.createElement('img');
                            addIcon.src = "img/add_media.svg";
                            addIcon.alt = "Add Icon";
                            addIcon.classList.add('add-icon');
                            button.prepend(addIcon);
                        }
                    });

                    experienceFormsContainer.appendChild(clonedBlock);
                });
            }
        });
    </script>
</body>
</html>
