const navLinks = document.querySelectorAll(".nav-menu .nav-link");
const menuOpenButton = document.querySelector("#menu-open-button");
const menuCloseButton = document.querySelector("#menu-close-button");
const contactForm = document.querySelector(".contact-form");

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

// Handle form submission
if (contactForm) {
    contactForm.addEventListener("submit", function (e) {
        e.preventDefault(); // Prevent default form submission

        let formData = new FormData(contactForm);

        fetch("submit_contact.php", {
            method: "POST",
            body: formData,
        })
        .then(response => response.text())
        .then(data => {
            console.log("Server response:", data); // Debugging output

            if (data.trim() === "success") {
                alert("Message sent successfully!");
                contactForm.reset(); // Reset the form
            } else {
                alert("Something went wrong: " + data); 
            }
        })
        .catch(error => console.error("Fetch error:", error));
    });

    document.addEventListener('DOMContentLoaded', function() {
        if (document.querySelector('.glide')) {
            new Glide('.glide', {
                type: 'carousel',
                perView: 3,
                gap: 20,
                autoplay: 3000,
                hoverpause: true,
                breakpoints: {
                    1024: {
                        perView: 2
                    },
                    768: {
                        perView: 1
                    }
                }
            }).mount();
        }
    });

}

document.getElementById('imageFile').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!validTypes.includes(file.type)) {
        alert('Only JPG, PNG or GIF images are allowed');
        this.value = '';
    }   
    
    if (file.size > 2 * 1024 * 1024) { // 2MB limit
        alert('Image must be less than 2MB');
        this.value = '';
    }
});
   
