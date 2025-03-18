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
