document.addEventListener('DOMContentLoaded', () => {
    const sliderContainers = document.querySelectorAll('.slider-container');

    sliderContainers.forEach(container => {
        const sliderImages = container.querySelector('.slider-images');
        const images = sliderImages.querySelectorAll('img');
        const prevBtn = container.querySelector('.prev-btn');
        const nextBtn = container.querySelector('.next-btn');
        let currentIndex = 0;

        function showImage(index) {
            if (index >= images.length) {
                currentIndex = 0;
            } else if (index < 0) {
                currentIndex = images.length - 1;
            } else {
                currentIndex = index;
            }
            sliderImages.style.transform = `translateX(${-currentIndex * 100}%)`;
        }

        prevBtn.addEventListener('click', () => {
            showImage(currentIndex - 1);
        });

        nextBtn.addEventListener('click', () => {
            showImage(currentIndex + 1);
        });

        // Initialize the first image
        showImage(0);
    });
});