<?php require_once 'includes/header.php'; ?>

<section id="contact" class="page-section bg-gray-100 py-16 min-h-screen">
  <div class="max-w-4xl mx-auto text-center mb-12">
    <h1 class="text-4xl font-bold mb-4 text-brand-dark">Contact Us</h1>
    <p class="text-xl text-gray-600">Have questions or suggestions? We'd love to hear from you.</p>
  </div>

  <div class="max-w-6xl mx-auto grid md:grid-cols-2 gap-12">
    <?php if (!empty($_SESSION['contact_success'])): ?>
  <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
    <?= $_SESSION['contact_success']; unset($_SESSION['contact_success']); ?>
  </div>
<?php endif; ?>

<?php if (!empty($_SESSION['contact_error'])): ?>
  <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
    <?= $_SESSION['contact_error']; unset($_SESSION['contact_error']); ?>
  </div>
<?php endif; ?>

    
    <!-- Contact Form -->
    <div class="bg-white p-8 rounded-lg shadow-md">
      <h2 class="text-2xl font-bold mb-6 text-brand-orange">Send us a Message</h2>
      <form id="contactForm" action="contact_handler.php" method="POST" class="space-y-6" novalidate>
        <div>
          <label for="contact-name" class="block text-gray-700 font-semibold mb-2">Your Name</label>
          <input type="text" id="contact-name" name="name" required
                 class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-orange" />
          <p class="text-red-600 mt-1 hidden text-sm" id="error-name">Please enter your name.</p>
        </div>
        <div>
          <label for="contact-email" class="block text-gray-700 font-semibold mb-2">Your Email</label>
          <input type="email" id="contact-email" name="email" required
                 class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-orange" />
          <p class="text-red-600 mt-1 hidden text-sm" id="error-email">Please enter a valid email.</p>
        </div>
        <div>
          <label for="contact-message" class="block text-gray-700 font-semibold mb-2">Message</label>
          <textarea id="contact-message" name="message" rows="5" required
                    class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-orange"></textarea>
          <p class="text-red-600 mt-1 hidden text-sm" id="error-message">Please enter your message.</p>
        </div>
        <button type="submit" 
                class="w-full bg-brand-orange text-white py-3 rounded-md hover:bg-orange-700 font-semibold transition duration-300">
          Send Message
        </button>
      </form>
    </div>

    <!-- Contact Info -->
    <div class="space-y-8">
      <div class="bg-white p-6 rounded-lg shadow-md flex items-center gap-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-brand-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16 12H8m8 4H8m8-8H8m-2 8v-4a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2z" />
        </svg>
        <div>
          <h3 class="font-semibold text-lg text-brand-dark">Email</h3>
          <a href="mailto:bookbankofficial.np@gmail.com" class="text-gray-600 hover:text-brand-orange transition">
            bookbankofficial.np@gmail.com
          </a>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow-md flex items-center gap-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-brand-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h1l1 1h11l1-1h1m-6 4v4m0 0a2 2 0 002-2m-2 2a2 2 0 01-2-2" />
        </svg>
        <div>
          <h3 class="font-semibold text-lg text-brand-dark">Phone</h3>
          <a href="tel:+9779814280201" class="text-gray-600 hover:text-brand-orange transition">
            +977 9814280201
          </a>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow-md flex items-center gap-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-brand-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 12.414a4 4 0 10-5.657 5.657l4.243 4.243a8 8 0 0011.314-11.314l-1.414 1.414a8 8 0 01-7.07 13.485z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h.01" />
        </svg>
        <div>
          <h3 class="font-semibold text-lg text-brand-dark">Address</h3>
          <p class="text-gray-600">Adarshnagar, Birgunj, Madhesh Province, Nepal</p>
        </div>
      </div>
    </div>
  </div>

</section>

<script>
  document.getElementById('contactForm').addEventListener('submit', function(event) {
    let valid = true;

    // Name validation
    const name = document.getElementById('contact-name');
    const errorName = document.getElementById('error-name');
    if (name.value.trim() === '') {
      errorName.classList.remove('hidden');
      valid = false;
    } else {
      errorName.classList.add('hidden');
    }

    // Email validation (simple regex)
    const email = document.getElementById('contact-email');
    const errorEmail = document.getElementById('error-email');
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email.value.trim())) {
      errorEmail.classList.remove('hidden');
      valid = false;
    } else {
      errorEmail.classList.add('hidden');
    }

    // Message validation
    const message = document.getElementById('contact-message');
    const errorMessage = document.getElementById('error-message');
    if (message.value.trim() === '') {
      errorMessage.classList.remove('hidden');
      valid = false;
    } else {
      errorMessage.classList.add('hidden');
    }

    if (!valid) {
      event.preventDefault();
    }
  });
</script>

<?php require_once 'includes/footer.php'; ?>
