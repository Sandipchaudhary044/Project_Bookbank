<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookBank - Sharing Knowledge for Free</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7fafc; /* A slightly off-white for the body */
        }
        /* You can define your brand colors here if you compile Tailwind, or use utility classes */
        .bg-brand-orange { background-color: #DD6B20; }
        .hover\:bg-brand-orange-dark:hover { background-color: #C05621; }
        .text-brand-dark { color: #2D3748; }
        .text-brand-orange { color: #DD6B20; }
        .book-card-image {
            aspect-ratio: 3 / 4;
            object-fit: cover;
        }
    </style>
</head>
<body class="text-gray-800">

<?php

// Include the header (which should ideally contain your database connection $conn)
require_once 'includes/header.php';


?>
    <main>
        <section class="relative bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-28">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div class="text-center md:text-left">
                        <h1 class="text-4xl md:text-6xl font-extrabold text-brand-dark tracking-tight">
                            Welcome to <span class="text-brand-orange"><br>Book Bank</span>
                        </h1>
                        <h3 class="text-2xl md:text-3xl font-semibold mb-6"><i>Sharing Knowledge for Free</i><h3>
                        <p class="mt-4 text-lg md:text-xl text-gray-600 max-w-lg mx-auto md:mx-0">
                            Your old book is a new book for those who haven't read it. Donate your used books on any subject, academic,non-academic, novels and picture story book.
                        </p>
                        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center md:justify-start">
                            <a href="available_books.php" class="bg-brand-orange text-white font-bold py-3 px-8 rounded-lg text-lg hover:bg-brand-orange-dark transition-transform transform hover:scale-105 duration-300 shadow-lg">Browse Books</a>
                            <a href="donate.php" class="bg-gray-200 text-gray-800 font-bold py-3 px-8 rounded-lg text-lg hover:bg-gray-300 transition-transform transform hover:scale-105 duration-300 shadow-lg">Donate a Book</a>
                        </div>
                    </div>
                    <div class="hidden md:block">
    <img 
        src="assets/images/info.jpg" 
        alt="A collection of books" 
        class="rounded-2xl shadow-2xl"
        onerror="this.onerror=null; this.src='https://placehold.co/600x400/1a202c/ffffff?text=Welcome';"
    >
</div>

                </div>
            </div>
        </section>
        
        <section class="py-16 sm:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-brand-dark">Recently Added Books</h2>
            <p class="mt-2 text-lg text-gray-500">Check out the latest additions to our community library.</p>
        </div>

        <?php
        // Fetch latest 8 approved books
        $query = "SELECT donation_id, book_title, author, cover_image, is_borrowed 
                  FROM donations 
                  WHERE status = 'approved' 
                  ORDER BY created_at DESC 
                  LIMIT 8";
        $result = $conn->query($query);
        ?>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                    <?php while ($book = $result->fetch_assoc()): 
                        $imagePath = !empty($book['cover_image']) ? 'uploads/' . $book['cover_image'] : 'assets/images/default_book.png';
                    ?>
                        <div class="group bg-white rounded-xl shadow-lg hover:shadow-2xl transition-shadow duration-300 flex flex-col overflow-hidden">
                            <div class="overflow-hidden relative">
                                <img src="<?= htmlspecialchars($imagePath); ?>"
                                     alt="<?= htmlspecialchars($book['book_title']); ?>"
                                     class="w-full book-card-image group-hover:scale-110 transition-transform duration-500 ease-in-out"
                                     onerror="this.onerror=null; this.src='assets/images/default_book.png';">
                                
                                <!-- Availability badge -->
                                <?php if ($book['is_borrowed'] == 1): ?>
                                    <span class="absolute top-2 left-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">Not Available</span>
                                <?php else: ?>
                                    <span class="absolute top-2 left-2 bg-green-600 text-white text-xs font-bold px-2 py-1 rounded">Available</span>
                                <?php endif; ?>
                            </div>
                            <div class="p-6 flex-1 flex flex-col">
                                <h3 class="text-xl font-bold text-brand-dark mb-1"><?= htmlspecialchars($book['book_title']); ?></h3>
                                <p class="text-gray-600 mb-4">by <?= htmlspecialchars($book['author']); ?></p>
                                
                                <!-- ✅ Link to book_details.php -->
                                <?php if (isset($_SESSION['user_id']) && $book['is_borrowed'] == 0): ?>
    <a href="request_borrow.php?id=<?= $book['donation_id']; ?>" 
       class="mt-auto w-full text-center bg-brand-orange text-white font-semibold py-2 px-4 rounded-lg hover:bg-brand-orange-dark transition duration-300">
        Request to Borrow
    </a>
<?php else: ?>
    <a href="book_details.php?donation_id=<?= $book['donation_id']; ?>" 
       class="mt-auto w-full text-center bg-gray-400 text-white font-semibold py-2 px-4 rounded-lg hover:bg-gray-500 transition duration-300">
        See Details
    </a>
<?php endif; ?>

                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-600">No featured books available right now. Please check back later.</p>
            <?php endif; ?>
        </div>
    </div>
</section>


    </main>

<?php
require_once 'includes/footer.php';
// Close the database connection if it was opened in this file
// if (isset($conn) && $conn instanceof mysqli) {
//     $conn->close();
// }
?>

    <script>
        // Mobile menu toggle script
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    </script>
</body>
</html>