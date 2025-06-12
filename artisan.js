document.addEventListener('DOMContentLoaded', () => {

    let scrollRightBtn = document.getElementById('scrollRight');
    let categoriesSlider = document.querySelector('.categories-scroll-slider');

    scrollRightBtn.addEventListener('click', () => {
        categoriesSlider.scrollBy({ left: 200, behavior: 'smooth' });
    });


    function renderStars(container) {
        let rating = parseFloat(container.dataset.rating);
        let totalStars = 5;
        container.innerHTML = ''; 

        for (let i = 1; i <= totalStars; i++) {
            let starImg = document.createElement('img');
            starImg.classList.add('review-star-icon');
            if (i <= rating) {
                starImg.src = 'img/review_group.svg'; 
                starImg.alt = 'Star';
            } else {
                starImg.src = 'img/empty_star.svg'; 
                starImg.alt = 'Empty Star';
            }
            container.appendChild(starImg);
        }
    }


    document.querySelectorAll('.star-rating-display').forEach(renderStars);



    let mainGalleryImage = document.querySelector('.main-gallery-image');
    let thumbnails = document.querySelectorAll('.gallery-thumbnail');
    let prevArrow = document.querySelector('.arrow-left');
    let nextArrow = document.querySelector('.arrow-right');

    let currentMediaIndex = 0;
    let mediaSources = Array.from(thumbnails).map(thumb => thumb.src); 

    

    function updateMainMedia(index) {
        if (index >= 0 && index < mediaSources.length) {
            mainGalleryImage.src = mediaSources[index];


            thumbnails.forEach((thumb, idx) => {
                if (idx === index) {
                    thumb.classList.add('active');
                } else {
                    thumb.classList.remove('active');
                }
            });
            currentMediaIndex = index;
        }
    }


    thumbnails.forEach((thumbnail, index) => {
        thumbnail.addEventListener('click', () => {
            updateMainMedia(index);
        });
    });


    prevArrow.addEventListener('click', () => {
        let newIndex = (currentMediaIndex - 1 + mediaSources.length) % mediaSources.length;
        updateMainMedia(newIndex);
    });

    nextArrow.addEventListener('click', () => {
        let newIndex = (currentMediaIndex + 1) % mediaSources.length;
        updateMainMedia(newIndex);
    });


    if (mediaSources.length > 0) {
        updateMainMedia(0); 
    }



    let calendarGrid = document.querySelector('.calendar-grid');
    let monthYearDisplay = document.querySelector('.calendar-month-year');
    let prevMonthArrow = document.querySelector('.prev-month');
    let nextMonthArrow = document.querySelector('.next-month');

    let currentDate = new Date();

    let reservedDates = [
        new Date(2025, 5, 4).toDateString(),  
        new Date(2025, 5, 10).toDateString(), 
        new Date(2025, 5, 18).toDateString()  
    ];

    function renderCalendar() {
        calendarGrid.innerHTML = ''; 
        monthYearDisplay.textContent = currentDate.toLocaleString('en-US', { month: 'long', year: 'numeric' });

        let firstDayOfMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
        let lastDayOfMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
        let lastDayOfPrevMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 0);

        let startDayIndex = firstDayOfMonth.getDay(); 


        for (let i = startDayIndex; i > 0; i--) {
            let date = lastDayOfPrevMonth.getDate() - i + 1;
            let span = document.createElement('span');
            span.classList.add('calendar-date', 'inactive');
            span.textContent = date;
            calendarGrid.appendChild(span);
        }


        for (let i = 1; i <= lastDayOfMonth.getDate(); i++) {
            let span = document.createElement('span');
            span.classList.add('calendar-date');
            span.textContent = i;

            let fullDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), i);


            let today = new Date();
            today.setHours(0, 0, 0, 0); 

            if (fullDate < today) {
                span.classList.add('inactive'); 
                span.style.cursor = 'default';
                span.style.backgroundColor = 'transparent';
            } else if (reservedDates.includes(fullDate.toDateString())) {
                span.classList.add('reserved');
            } else {

                span.addEventListener('click', () => {

                    let previouslySelected = document.querySelector('.calendar-date.selected');
                    if (previouslySelected) {
                        previouslySelected.classList.remove('selected');

                        if (!previouslySelected.classList.contains('reserved') && !previouslySelected.classList.contains('inactive')) {
                            previouslySelected.style.backgroundColor = '';
                        }
                    }

                    span.classList.add('selected');
                    span.style.backgroundColor = '#3E185B'; 
                    span.style.color = '#fff';
                    console.log(`Selected date: ${fullDate.toDateString()}`);

                });
            }
            calendarGrid.appendChild(span);
        }


        let totalCells = startDayIndex + lastDayOfMonth.getDate();
        let remainingCells = 42 - totalCells; 
        if (totalCells % 7 !== 0) { 
            for (let i = 1; i <= (7 - (totalCells % 7)); i++) {
                let span = document.createElement('span');
                span.classList.add('calendar-date', 'inactive');
                span.textContent = i;
                calendarGrid.appendChild(span);
            }
        }
    }


    prevMonthArrow.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });

    nextMonthArrow.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });


    renderCalendar();

});