// Function to check if the user prefers reduced motion
const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

document.addEventListener('DOMContentLoaded', () => {
    // JavaScript Animation: Text sequence appearance in the Hero section
    // Runs only if reduced motion is not preferred
    if (!prefersReducedMotion) {
        anime.timeline({
            easing: 'easeOutExpo',
            duration: 750,
            delay: anime.stagger(100) // Delay between each element
        })
        .add({
            targets: '.js-sequence-item',
            opacity: [0, 1],
            translateY: [20, 0]
        });
    }

    // JavaScript Animation: Complex animation on button click
    const jsComplexAnimationBtn = document.getElementById('jsComplexAnimationBtn');
    const explosionContainer = document.getElementById('explosionContainer');

    if (jsComplexAnimationBtn && explosionContainer) {
        jsComplexAnimationBtn.addEventListener('click', () => {
            // Clear any previous explosion elements
            explosionContainer.innerHTML = '';

            // Create and animate 50 small "particles"
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.classList.add('absolute', 'w-2', 'h-2', 'rounded-full');
                particle.style.backgroundColor = `hsl(${Math.random() * 360}, 70%, 50%)`; // Random color
                explosionContainer.appendChild(particle);

                // Animate each particle
                anime({
                    targets: particle,
                    translateX: anime.random(-100, 100), // Random horizontal movement
                    translateY: anime.random(-100, 100), // Random vertical movement
                    scale: [0, 1], // Appear and scale
                    opacity: [1, 0], // Fade out
                    duration: anime.random(800, 1500), // Random duration
                    easing: 'easeOutQuad',
                    delay: anime.random(0, 300), // Random delay
                    // Only if reduced motion is not preferred
                    autoplay: !prefersReducedMotion
                });
            }
        });
    }

    // Scroll-driven animations (Intersection Observer)
    const scrollRevealItems = document.querySelectorAll('.scroll-reveal-item');

    const observerOptions = {
        root: null, // The viewport is the root
        rootMargin: '0px',
        threshold: 0.1 // When 10% of the element is visible
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // If the element is visible, add the class to animate it
                entry.target.classList.add('is-visible');
                // Stop observing the element once it has been animated
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe each element that should be revealed on scroll
    scrollRevealItems.forEach(item => {
        observer.observe(item);
    });

    // Handle Contact Form Submission
    const contactForm = document.getElementById('contactForm');
    const formMessage = document.getElementById('formMessage');

    if (contactForm) {
        contactForm.addEventListener('submit', (event) => {
            event.preventDefault(); // Prevent default form submission

            // In a real application, you would send this data to a server
            // For now, we'll just log it and show a success message
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const message = document.getElementById('message').value;

            console.log('Formulario Enviado:');
            console.log(`Nombre: ${name}`);
            console.log(`Email: ${email}`);
            console.log(`Mensaje: ${message}`);

            // Show success message
            formMessage.classList.remove('hidden');
            contactForm.reset(); // Clear the form

            // Hide message after a few seconds
            setTimeout(() => {
                formMessage.classList.add('hidden');
            }, 5000);
        });
    }
});
