const navLinks = document.querySelectorAll(".nav-menu .nav-link");
const menuOpenButton = document.querySelector("#menu-open-button");
const menCloseButton = document.querySelector("#menu-close-button");

//toggles mobile menu visibility
menuOpenButton.addEventListener("click", () => {
    document.body.classList.toggle("show-mobile-menu");
});

//closes menu when close button is clicked
menCloseButton.addEventListener("click", () => { menuOpenButton.click();  

});

//closes menu when nav link is clicked
navLinks.forEach(link => {
    link.addEventListener("click", () => 
        menuOpenButton.click());
    });


    function showSection(sectionId) {
        // Hide all sections
        document.querySelectorAll('.section').forEach(section => {
            section.classList.remove('active');
        });

        // Show the clicked section
        document.getElementById(sectionId).classList.add = ('active');
    }

    // Show only the home section by default
    document.addEventListener("DOMContentLoaded", function ()  {
        showSection('home');
    });

    