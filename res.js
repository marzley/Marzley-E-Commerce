
    document.getElementById('contact-form').onsubmit = function(e) {
        e.preventDefault();
        document.getElementById('form-msg').textContent = 'Thank you for contacting us! We will get back to you soon.';
        document.getElementById('form-msg').className = 'msg success';
        this.reset();
    };

    // Hamburger menu functionality
    const hamburger = document.getElementById('hamburger');
    const navLinks = document.getElementById('navLinks');
    hamburger.onclick = function() {
        navLinks.classList.toggle('open');
    };
    // Optional: Close menu when a link is clicked (on small screens)
    navLinks.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 700) navLinks.classList.remove('open');
        });
    });
