document.addEventListener('DOMContentLoaded', function() {
    const mainGalleryImage = document.querySelector('.main-gallery-image');
    const thumbnails = document.querySelectorAll('.gallery-thumbnail');
    const arrowLeft = document.querySelector('.arrow-left');
    const arrowRight = document.querySelector('.arrow-right');
    const mediaGallery = document.querySelector('.artisan-media-gallery');

    let currentImageIndex = 0;
    const images = Array.from(thumbnails).map(thumb => thumb.src);

    function updateMainImage(index) {
        if (images.length === 0) {
            mainGalleryImage.src = ''; // Or a placeholder image
            return;
        }
        currentImageIndex = (index + images.length) % images.length;
        mainGalleryImage.src = images[currentImageIndex];

        thumbnails.forEach((thumb, i) => {
            if (i === currentImageIndex) {
                thumb.classList.add('active');
            } else {
                thumb.classList.remove('active');
            }
        });
    }

    arrowLeft.addEventListener('click', () => {
        updateMainImage(currentImageIndex - 1);
    });

    arrowRight.addEventListener('click', () => {
        updateMainImage(currentImageIndex + 1);
    });

    thumbnails.forEach((thumbnail, index) => {
        thumbnail.addEventListener('click', () => {
            updateMainImage(index);
        });
    });

    // Initialize the first image
    updateMainImage(0);


    const requestServiceBtn = document.querySelector('.request-service-action-btn');

    requestServiceBtn.addEventListener('click', function() {
        const prestataireId = this.dataset.prestataireId;
        const requiresLogin = this.dataset.requiresLogin === 'true';

        if (requiresLogin && !isLoggedIn) {
            window.location.href = 'login.php';
        } else {
            window.location.href = `demande_service.php?id_prestataire=${prestataireId}`;
        }
    });
});