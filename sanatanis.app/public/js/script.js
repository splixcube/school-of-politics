document.addEventListener("DOMContentLoaded", function () {
  // Set the target date - July 21, 2025 at midnight
  const targetDate = new Date("2025-07-21T00:00:00").getTime();

  // Cache DOM elements for better performance
  const countdownContainer = document.getElementById("countdown");
  const daysElement = document.querySelector(".days");
  const hoursElement = document.querySelector(".hours");
  const minutesElement = document.querySelector(".minutes");
  const secondsElement = document.querySelector(".seconds");

  // Update the countdown every second
  const countdownInterval = setInterval(updateCountdown, 1000);

  // Initial call to display immediately
  updateCountdown();

  function updateCountdown() {
    const now = new Date().getTime();
    const distance = targetDate - now;

    // If the countdown is finished
    if (distance < 0) {
      clearInterval(countdownInterval);
      countdownContainer.innerHTML = `
                <p class="warning my-3 my-md-0" >
                    <img src="img/icons/ic_warning_red_info.svg" class="img-fluid" alt="">
                    <span>Bookings has been closed for this Puja</span>
                </p>
            `;
      return;
    }

    // Calculate time units
    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
    const hours = Math.floor(
      (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
    );
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

    // Format numbers to always show 2 digits
    const formatNumber = (num) => num.toString().padStart(2, "0");

    // Update the display using cached elements
    daysElement.textContent = formatNumber(days);
    hoursElement.textContent = formatNumber(hours);
    minutesElement.textContent = formatNumber(minutes);
    secondsElement.textContent = formatNumber(seconds);
  }
});

// Initialize Owl Carousel for sliders
$(document).ready(function () {
  $(".slider").each(function() {
    const $carousel = $(this);
    const itemCount = $carousel.find('.item').length;
    
    $carousel.owlCarousel({
      loop: true,
      margin: 10,
      nav: itemCount > 1,
      navText: [
        '<img src="img/icons/prev.svg" alt="Previous">',
        '<img src="img/icons/next.svg" alt="Next">',
      ],
      dots: itemCount > 1,
      autoplay: itemCount > 1,
      autoplayTimeout: 3000,
      autoplayHoverPause: true,
      responsive: {
        0: {
          items: 1,
        },
        600: {
          items: 1,
        },
        1000: {
          items: 1,
        },
      },
    });
    
    // Hide navigation arrows if only one item
    if (itemCount <= 1) {
      $carousel.find('.owl-nav').hide();
    }
  });
});

$(".slider-2").each(function() {
  const $carousel = $(this);
  const itemCount = $carousel.find('.item').length;
  
  $carousel.owlCarousel({
    loop: false,
    margin: 30,
    nav: itemCount > 1,
    navText: [
      '<i class="fas fa-chevron-left"></i>',
      '<i class="fas fa-chevron-right"></i>',
    ],
    dots: itemCount > 1,
    autoplay: false,
    autoWidth: true,
    responsive: {
      0: {
        items: 1,
      },
      600: {
        items: 2,
      },
      1000: {
        items: 2,
      },
    },
  });
  
  // Hide navigation arrows if only one item
  if (itemCount <= 1) {
    $carousel.find('.owl-nav').hide();
  }
});

$(".slider-testimonial").each(function() {
  const $carousel = $(this);
  const itemCount = $carousel.find('.item').length;
  
  $carousel.owlCarousel({
    loop: false,
    margin: 30,
    nav: itemCount > 1,
    navText: [
      '<i class="fas fa-chevron-left"></i>',
      '<i class="fas fa-chevron-right"></i>',
    ],
    dots: itemCount > 1,
    autoplay: false,
    responsive: {
      0: {
        items: 1,
      },
      600: {
        items: 4,
      },
      1000: {
        items: 4,
      },
    },
  });
  
  // Hide navigation arrows if only one item
  if (itemCount <= 1) {
    $carousel.find('.owl-nav').hide();
  }
});

// Truncated text functionality
document.addEventListener("DOMContentLoaded", function () {
  const readMoreButtons = document.querySelectorAll(".read-more-btn");

  readMoreButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const textElement = this.previousElementSibling;
      const isExpanded = textElement.classList.contains("expanded");

      if (isExpanded) {
        textElement.classList.remove("expanded");
        textElement.classList.add("truncated");
        this.textContent = "Read More";
      } else {
        textElement.classList.remove("truncated");
        textElement.classList.add("expanded");
        this.textContent = "Read Less";
      }
    });
  });

  // Active Navigation functionality
  const navLinks = document.querySelectorAll(".nav-custom");
  const sections = document.querySelectorAll("section[id]");

  function updateActiveNav() {
    let current = "";
    const scrollPosition = window.scrollY + 100; // Offset for navbar height

    sections.forEach((section) => {
      const sectionTop = section.offsetTop;
      const sectionHeight = section.offsetHeight;

      if (
        scrollPosition >= sectionTop &&
        scrollPosition < sectionTop + sectionHeight
      ) {
        current = section.getAttribute("id");
      }
    });

    navLinks.forEach((link) => {
      link.classList.remove("active");
      if (link.getAttribute("href") === `#${current}`) {
        link.classList.add("active");
      }
    });
  }

  // Update active nav on scroll
  window.addEventListener("scroll", updateActiveNav);

  // Update active nav on load
  updateActiveNav();

  // Smooth scroll for navigation links
  navLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      const targetId = this.getAttribute("href");
      const targetSection = document.querySelector(targetId);

      if (targetSection) {
        const navHeight = document.querySelector(".navbar").offsetHeight;
        const targetPosition = targetSection.offsetTop - navHeight - 20;

        window.scrollTo({
          top: targetPosition,
          behavior: "smooth",
        });
      }
    });
  });
});

// Dynamic year in footer
document.getElementById("year").textContent = new Date().getFullYear();
