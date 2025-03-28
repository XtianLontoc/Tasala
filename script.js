const navLinks = document.querySelectorAll(".nav-menu .nav-link");
const menuOpenButton = document.querySelector("#menu-open-button");
const menuCloseButton = document.querySelector("#menu-close-button");

// Toggles mobile menu visibility
menuOpenButton.addEventListener("click", () => {
    document.body.classList.toggle("show-mobile-menu");
});

// Closes menu when close button is clicked
menuCloseButton.addEventListener("click", () => {
    menuOpenButton.click();
});

// Closes menu when nav link is clicked
navLinks.forEach(link => {
    link.addEventListener("click", () => {
        menuOpenButton.click();
    });
});

function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });

    // Show the clicked section
    document.getElementById(sectionId).classList.add('active');
}

// Show only the home section by default
document.addEventListener("DOMContentLoaded", function () {
    showSection('home');
});

window.addEventListener('resize', () => {
    const video = document.querySelector('.background-video');
    if (window.innerWidth <= 768) {
        video.style.height = '100%';
        video.style.width = '100%';
    }
});