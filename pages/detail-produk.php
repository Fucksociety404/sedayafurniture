<?php
include '../config/koneksi.php';

$produk = null;
$related_products = [];
$error_message = null;
$page_title = 'Detail Produk'; // Default title
$meta_description = 'Lihat detail produk kami.'; // Default description
$meta_keywords = 'produk, detail'; // Default keywords
$canonical_url = ''; // Default canonical URL
$og_image = ''; // Default Open Graph image
$schema_product = null; // For JSON-LD structured data

// Function to generate a clean description snippet
function generate_meta_description($text, $length = 160) {
    $text = strip_tags($text); // Remove HTML tags
    $text = preg_replace('/\s+/', ' ', $text); // Replace multiple spaces with single space
    $text = trim($text);
    if (mb_strlen($text) > $length) {
        $text = mb_substr($text, 0, $length - 3) . '...';
    }
    return htmlspecialchars($text);
}

// Function to get the current page URL
function get_current_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $request_uri = $_SERVER['REQUEST_URI'];
    return $protocol . $host . $request_uri;
}


if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_produk = mysqli_real_escape_string($con, $_GET['id']);
    // Fetch main product including price
    $query = "SELECT p.*, k.nama AS nama_kategori
              FROM produk p
              LEFT JOIN kategori k ON p.kategori_id = k.id
              WHERE p.id = '$id_produk'";
    $result = mysqli_query($con, $query);
    $produk = mysqli_fetch_assoc($result);

    if ($produk) {
        // --- SEO Data Preparation ---
        $page_title = htmlspecialchars($produk['nama']) . ' - Detail Produk';
        $canonical_url = get_current_url(); // Set canonical URL

        // Extract size and prepare description
        $deskripsi_lines = explode("\n", trim($produk['deskripsi'] ?? ''));
        $ukuran_teks = '';
        $deskripsi_display_lines = [];
        foreach ($deskripsi_lines as $line) {
            $line = trim($line);
            if (stripos($line, 'p=') === 0) {
                $ukuran_teks .= 'P: ' . trim(substr($line, 2)) . 'cm ';
            } elseif (stripos($line, 'l=') === 0) {
                $ukuran_teks .= 'L: ' . trim(substr($line, 2)) . 'cm ';
            } elseif (stripos($line, 't=') === 0) {
                $ukuran_teks .= 'T: ' . trim(substr($line, 2)) . 'cm';
            } else {
                $deskripsi_display_lines[] = $line; // Keep non-size lines for description
            }
        }
        $deskripsi_display = implode("\n", $deskripsi_display_lines);
        $meta_description = generate_meta_description($deskripsi_display ?: $produk['nama']); // Use description or name for meta

        // Prepare keywords
        $keywords_array = [$produk['nama'], $produk['nama_kategori'], $produk['bahan'], 'detail produk'];
        $keywords_array = array_filter($keywords_array); // Remove empty values
        $meta_keywords = htmlspecialchars(implode(', ', array_unique($keywords_array)));

        // --- Image Handling ---
        $kode_produk = $produk['kode_produk'];
        $base_url_path = dirname(get_current_url()) . '/'; // Get base URL path for images
        $folder_produk_rel = "../images/produk/" . $kode_produk . "/";
        $folder_produk_abs = str_replace('../', $base_url_path, $folder_produk_rel); // Absolute URL path

        $foto_utama_rel = !empty($produk['foto']) ? $folder_produk_rel . $produk['foto'] : '';
        $thumbnail1_rel = !empty($produk['foto_thumbnail1']) ? $folder_produk_rel . $produk['foto_thumbnail1'] : '';
        $thumbnail2_rel = !empty($produk['foto_thumbnail2']) ? $folder_produk_rel . $produk['foto_thumbnail2'] : '';
        $thumbnail3_rel = !empty($produk['foto_thumbnail3']) ? $folder_produk_rel . $produk['foto_thumbnail3'] : '';

        // Prepare array of valid image paths (relative for file_exists, absolute for web)
        $all_image_paths_rel = [];
        $all_image_paths_abs = [];
        if ($foto_utama_rel && file_exists($foto_utama_rel)) {
             $all_image_paths_rel[] = $foto_utama_rel;
             $all_image_paths_abs[] = $folder_produk_abs . $produk['foto'];
        }
        if ($thumbnail1_rel && file_exists($thumbnail1_rel)) {
             $all_image_paths_rel[] = $thumbnail1_rel;
             $all_image_paths_abs[] = $folder_produk_abs . $produk['foto_thumbnail1'];
        }
        if ($thumbnail2_rel && file_exists($thumbnail2_rel)) {
             $all_image_paths_rel[] = $thumbnail2_rel;
             $all_image_paths_abs[] = $folder_produk_abs . $produk['foto_thumbnail2'];
        }
        if ($thumbnail3_rel && file_exists($thumbnail3_rel)) {
             $all_image_paths_rel[] = $thumbnail3_rel;
             $all_image_paths_abs[] = $folder_produk_abs . $produk['foto_thumbnail3'];
        }
        $all_image_paths_rel = array_unique($all_image_paths_rel); // Ensure uniqueness
        $all_image_paths_abs = array_unique($all_image_paths_abs); // Ensure uniqueness

        // Set main image to the first valid path, or null if none exist
        $main_image_display_rel = !empty($all_image_paths_rel) ? $all_image_paths_rel[0] : null;
        $main_image_display_abs = !empty($all_image_paths_abs) ? $all_image_paths_abs[0] : null;
        $og_image = $main_image_display_abs; // Use main image for Open Graph

        // --- Related Products ---
        $kategori_id = $produk['kategori_id'];
        if ($kategori_id) {
            $related_query = "SELECT id, nama, foto, kode_produk FROM produk WHERE kategori_id = '$kategori_id' AND id != '$id_produk' ORDER BY RAND() LIMIT 4";
            $related_result = mysqli_query($con, $related_query);
            while ($row = mysqli_fetch_assoc($related_result)) {
                $related_products[] = $row;
            }
        }

        // --- Prepare Schema.org JSON-LD ---
        $schema_product = [
            "@context" => "https://schema.org/",
            "@type" => "Product",
            "name" => htmlspecialchars($produk['nama']),
            "image" => $main_image_display_abs ? [$main_image_display_abs] : [], // Image must be an array
            "description" => generate_meta_description($deskripsi_display ?: $produk['nama'], 5000), // Longer description allowed here
            "sku" => htmlspecialchars($produk['kode_produk']),
            // "brand" => [ // Optional: Add brand if available
            //     "@type" => "Brand",
            //     "name" => "Nama Brand Anda"
            // ],
            "category" => htmlspecialchars($produk['nama_kategori'] ?? 'Tidak Dikategorikan'),
            "offers" => [
                "@type" => "Offer",
                "url" => $canonical_url,
                "availability" => ($produk['stok'] > 0) ? "https://schema.org/InStock" : "https://schema.org/PreOrder",
                // Add price if available and relevant
                // "priceCurrency" => "IDR", // Change if needed
                // "price" => isset($produk['harga']) ? number_format($produk['harga'], 2, '.', '') : null, // Format price correctly
            ]
        ];
        // Remove price keys if price is null
        // if ($schema_product['offers']['price'] === null) {
        //     unset($schema_product['offers']['price']);
        //     unset($schema_product['offers']['priceCurrency']);
        // }


    } else {
        $error_message = "Produk dengan ID tersebut tidak ditemukan.";
        // Set noindex for error pages
        header("X-Robots-Tag: noindex, follow", true);
    }
} else {
    $error_message = "ID produk tidak valid.";
    // Set noindex for error pages
    header("X-Robots-Tag: noindex, follow", true);
}
?>

<!DOCTYPE html>
<html lang="id" <?php if ($produk) echo 'itemscope itemtype="https://schema.org/Product"'; ?>>
<head>

   <!-- Google tag (gtag.js) -->
   <script async src="https://www.googletagmanager.com/gtag/js?id=G-LHH00CCQR9"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-LHH00CCQR9');
</script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $meta_description; ?>">
    <meta name="keywords" content="<?php echo $meta_keywords; ?>">
    <?php if ($canonical_url): ?>
    <link rel="canonical" href="<?php echo $canonical_url; ?>" />
    <?php endif; ?>
    <meta name="robots" content="index, follow"> <!-- Default robots directive -->

    <!-- Open Graph Meta Tags (for Facebook, LinkedIn, etc.) -->
    <?php if ($produk): ?>
    <meta property="og:title" content="<?php echo htmlspecialchars($produk['nama']); ?>" />
    <meta property="og:description" content="<?php echo $meta_description; ?>" />
    <meta property="og:type" content="product" />
    <meta property="og:url" content="<?php echo $canonical_url; ?>" />
    <?php if ($og_image): ?>
    <meta property="og:image" content="<?php echo $og_image; ?>" />
    <?php endif; ?>
    <?php /* <meta property="og:site_name" content="Nama Toko Anda" /> */ ?>
    <?php /* if (isset($produk['harga'])): ?>
    <meta property="product:price:amount" content="<?php echo number_format($produk['harga'], 2, '.', ''); ?>" />
    <meta property="product:price:currency" content="IDR" />
    <?php endif; */ ?>
    <meta property="product:availability" content="<?php echo ($produk['stok'] > 0) ? 'instock' : 'preorder'; ?>" />
    <?php endif; ?>

    <!-- Twitter Card Meta Tags -->
    <?php if ($produk): ?>
    <meta name="twitter:card" content="summary_large_image"> <!-- Use summary_large_image if you have good images -->
    <meta name="twitter:title" content="<?php echo htmlspecialchars($produk['nama']); ?>">
    <meta name="twitter:description" content="<?php echo $meta_description; ?>">
    <?php if ($og_image): ?>
    <meta name="twitter:image" content="<?php echo $og_image; ?>">
    <?php endif; ?>
    <?php /* <meta name="twitter:site" content="@UsernameTwitterAnda"> */ ?>
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" href="../images/favicon.png" type="image/x-icon"> <!-- Tambahkan path ke favicon Anda -->

    <!-- Stylesheets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Inline Styles (Keep as is) -->
    <style>
        :root {
            --primary-color: #2d3748;
            --secondary-color: #718096;
            --accent-color: #4a5568;
            --light-bg: #f7fafc;
            --border-color: #e2e8f0;
            --success-color: #48bb78;
            --discount-color: #e53e3e;
            --preorder-color: #e53e3e; /* Added color for pre-order */
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--primary-color);
            background-color: var(--light-bg);
            margin: 0;
            padding: 0;
        }

        .product-detail-page {
            padding: 20px;
            margin-top: 60px; /* Adjust based on your navbar height */
        }

        .container-xl { /* Use wider container */
            max-width: 1200px; /* Wider container */
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        /* Main product content layout */
        .product-main-content {
            display: flex;
            flex-direction: column; /* Stack gallery and info vertically on small screens */
        }

        @media (min-width: 768px) { /* Adjust breakpoint if needed */
            .product-main-content {
                flex-direction: row; /* Side-by-side gallery and info on medium+ screens */
                gap: 30px;
            }
        }

        .product-gallery {
            flex-basis: 100%; /* Full width on small screens */
            margin-bottom: 20px; /* Space below gallery on small screens */
        }
        @media (min-width: 768px) {
            .product-gallery {
                flex-basis: 40%; /* Adjust basis as needed */
                margin-bottom: 0;
            }
        }

        .main-image-container {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 15px;
            width: 100%;
            background-color: #f8f9fa; /* Light background for placeholder */
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 300px; /* Ensure container has height */
        }

        .main-image {
            width: 100%;
            height: auto;
            display: block;
            max-height: 400px;
            object-fit: contain;
        }

        .thumbnails-container {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            margin-top: 10px;
            width: 100%;
            padding-bottom: 5px; /* Space for scrollbar if needed */
        }

        .thumbnail {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border: 2px solid var(--border-color); /* Slightly thicker border */
            border-radius: 4px;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.3s ease, border-color 0.3s ease;
            flex-shrink: 0; /* Prevent thumbnails from shrinking */
        }

        .thumbnail:hover {
            opacity: 1;
        }
        .thumbnail.active {
            opacity: 1;
            border-color: var(--accent-color);
        }

        .product-info-column {
             /* Styles specific to the column holding product info, description, etc. */
        }

        .product-info {
            /* Styles for the core product details (title, meta, price, actions) */
            margin-bottom: 30px; /* Space before description */
        }

        .product-title {
            font-size: 2.0em;
            margin-bottom: 10px;
            color: var(--primary-color);
            font-weight: 600;
            /* Microdata for product name */
            <?php if ($produk) echo 'itemprop="name"'; ?>
        }

        .product-meta {
            margin-bottom: 15px;
            font-size: 0.9em;
            color: var(--secondary-color);
        }

        .product-meta p {
            margin-bottom: 5px;
        }

        .product-meta strong {
            font-weight: 500;
            margin-right: 5px;
            color: var(--primary-color); /* Make labels slightly darker */
        }

        /* Style for Pre Order text */
        .pre-order-status {
            color: var(--preorder-color);
            font-weight: bold;
        }

        .price-section { /* Added for potential future price styling */
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .current-price {
            font-size: 1.8em;
            color: var(--accent-color);
            font-weight: bold;
            margin-right: 10px;
            /* Microdata for price */
            <?php if ($produk && isset($produk['harga'])) echo 'itemprop="price" content="' . number_format($produk['harga'], 2, '.', '') . '"'; ?>
        }
        /* Microdata for currency */
        <?php if ($produk && isset($produk['harga'])) echo '<meta itemprop="priceCurrency" content="IDR" />'; ?>


        /* Add styles for original price and discount if needed */

        .quantity-section {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            font-size: 0.9em;
        }

        .quantity-label {
            margin-right: 10px;
            font-weight: 500;
            color: var(--primary-color);
        }

        .quantity-input {
            width: 60px;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            text-align: center;
        }
        .quantity-input:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap; /* Allow buttons to wrap on smaller screens */
            gap: 10px;
            margin-bottom: 30px;
        }

        .add-to-cart-btn, .buy-now-btn {
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s ease;
            text-decoration: none;
            color: white;
            font-size: 1em;
            flex-grow: 1; /* Allow buttons to grow */
        }
         @media (min-width: 576px) {
             .add-to-cart-btn, .buy-now-btn {
                 flex-grow: 0; /* Prevent growing on larger screens */
             }
         }


        .add-to-cart-btn {
            background-color: var(--success-color);
        }
        .add-to-cart-btn:hover:not(:disabled) {
            background-color: #38a169;
        }

        .buy-now-btn {
            background-color: var(--accent-color);
        }
        .buy-now-btn:hover:not(:disabled) {
            background-color: #374151;
        }
        .add-to-cart-btn:disabled, .buy-now-btn:disabled {
            background-color: var(--secondary-color);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .product-description {
            margin-top: 0; /* Reset margin as it's now part of the flow */
            padding: 20px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background-color: #fff;
            font-size: 0.9em;
            color: var(--secondary-color);
            line-height: 1.7;
            margin-bottom: 20px; /* Space before share section */
            /* Microdata for description */
            <?php if ($produk) echo 'itemprop="description"'; ?>
        }

        .product-description h3 {
            font-size: 1.4em;
            color: var(--primary-color);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .product-description p {
            margin-bottom: 15px;
        }

        .product-description ul {
            padding-left: 20px;
            margin-bottom: 15px;
        }

        .product-description li {
            margin-bottom: 5px;
        }

        .share-section {
            margin-top: 0; /* Reset margin */
            font-size: 0.9em;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background-color: var(--light-bg);
            border-radius: 6px;
        }

        .share-label {
            font-weight: 500;
            color: var(--primary-color);
        }

        .share-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #fff; /* White background for icons */
            color: var(--secondary-color);
            border: 1px solid var(--border-color);
            text-decoration: none;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .share-icons a:hover {
            background-color: var(--accent-color);
            color: #fff;
            border-color: var(--accent-color);
        }

        .share-icons i {
            font-size: 1.1em;
        }

        /* Related Products Sidebar Styles */
        .related-products-sidebar {
            padding: 20px;
            background-color: var(--light-bg); /* Light background for sidebar */
            border-radius: 8px;
            border: 1px solid var(--border-color);
            height: 100%; /* Optional: make sidebar full height */
        }

        .related-products-title {
            font-size: 1.4em; /* Slightly larger title */
            color: var(--primary-color);
            margin-bottom: 25px; /* More space below title */
            font-weight: 600;
            text-align: left; /* Align title left */
            padding-bottom: 15px; /* More space below title line */
            border-bottom: 1px solid var(--border-color);
        }

        .related-product-card {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            overflow: hidden;
            transition: box-shadow 0.3s ease;
            background-color: #fff; /* White background for cards */
            text-align: left; /* Align text left */
            display: flex; /* Use flex for better alignment */
            align-items: center; /* Vertically align items */
            /* gap: 25px; */ /* Removed gap from here */
            padding: 15px; /* Increased padding inside the card */
        }

        .related-product-card:hover {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-color: var(--accent-color);
        }

        .related-product-card a {
            text-decoration: none;
            color: inherit;
            display: flex; /* Make link cover the flex container */
            align-items: center;
            width: 100%; /* Ensure link takes full width */
            gap: 35px; /* Increased space between image and text */
        }

        .related-product-image-container {
            flex-shrink: 0; /* Prevent image container from shrinking */
            width: 80px; /* Increased width for image container */
            height: 80px; /* Increased height */
            border: 1px solid var(--border-color);
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .related-product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .related-product-info {
            flex-grow: 1; /* Allow info to take remaining space */
            padding: 0; /* Remove padding here, added to card */
        }

        .related-product-name {
            font-size: 1em; /* Ukuran font diperbesar */
            font-weight: 500;
            color: var(--primary-color);
            margin: 0; /* Hapus margin default */
            line-height: 1.4; /* Tinggi baris disesuaikan */
            /* Batasi teks menjadi 2 baris */
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .no-image-placeholder {
            width: 100%;
            height: 100%;
            background-color: var(--light-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary-color);
            font-size: 0.8em; /* Smaller text */
            text-align: center;
        }
        .no-image-placeholder.related {
             font-size: 0.8em; /* Increased size for related placeholder */
             padding: 5px;
        }

    </style>

    <!-- Schema.org JSON-LD -->
    <?php if ($schema_product): ?>
    <script type="application/ld+json">
    <?php echo json_encode($schema_product, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
    </script>
    <?php endif; ?>

</head>

<body>
    <?php include 'templat/header.php'; ?>

    <div class="product-detail-page">
        <div class="container-xl"> <!-- Changed to container-xl -->
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php elseif ($produk): ?>
                <!-- Microdata for Offer -->
                <div <?php if ($produk) echo 'itemprop="offers" itemscope itemtype="https://schema.org/Offer"'; ?>>
                    <?php if (isset($produk['harga'])): ?>
                        <meta itemprop="priceCurrency" content="IDR">
                        <meta itemprop="price" content="<?php echo number_format($produk['harga'], 2, '.', ''); ?>">
                    <?php endif; ?>
                    <link itemprop="availability" href="https://schema.org/<?php echo ($produk['stok'] > 0) ? 'InStock' : 'PreOrder'; ?>" />
                    <link itemprop="url" href="<?php echo $canonical_url; ?>" />
                </div>

                <div class="row">
                    <!-- Main Product Content Column -->
                    <div class="col-lg-8">
                        <div class="product-main-content mb-4">
                            <div class="product-gallery">
                                <div class="main-image-container">
                                    <?php if ($main_image_display_rel) : ?>
                                        <img src="<?php echo htmlspecialchars($main_image_display_rel); ?>"
                                             alt="<?php echo htmlspecialchars($produk['nama']); ?>"
                                             class="main-image" id="mainImage"
                                             <?php if ($produk) echo 'itemprop="image"'; ?>> <!-- Microdata for image -->
                                    <?php else : ?>
                                        <div class="no-image-placeholder">Gambar Utama Tidak Tersedia</div>
                                    <?php endif; ?>
                                </div>
                                <?php if (count($all_image_paths_rel) > 1) : // Show thumbnails only if more than one valid image exists ?>
                                <div class="thumbnails-container">
                                    <?php
                                    foreach ($all_image_paths_rel as $index => $thumbnail_path_rel) :
                                        $alt_text = htmlspecialchars($produk['nama']) . ' - Detail ' . ($index + 1);
                                        $is_active = ($thumbnail_path_rel === $main_image_display_rel);
                                        ?>
                                        <img src="<?php echo htmlspecialchars($thumbnail_path_rel); ?>" alt="<?php echo $alt_text; ?>"
                                             class="thumbnail <?php if ($is_active) echo 'active'; ?>"
                                             onclick="changeImage('<?php echo htmlspecialchars($thumbnail_path_rel); ?>', this)"
                                             title="Lihat Detail <?php echo ($index + 1); ?>">
                                        <?php
                                    endforeach;
                                    ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="product-info">
                                <h1 class="product-title"><?php echo htmlspecialchars($produk['nama']); ?></h1>
                                <div class="product-meta">
                                    <p><strong>Kode Produk:</strong> <span <?php if ($produk) echo 'itemprop="sku"'; ?>><?php echo htmlspecialchars($produk['kode_produk']); ?></span></p>
                                    <?php if (!empty($produk['nama_kategori'])): ?>
                                    <p><strong>Kategori:</strong> <?php echo htmlspecialchars($produk['nama_kategori']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($produk['bahan'])): ?>
                                    <p><strong>Bahan:</strong> <?php echo htmlspecialchars($produk['bahan']); ?></p>
                                    <?php endif; ?>
                                    <p>
                                        <strong>Ukuran:</strong>
                                        <?php
                                        // Ukuran calculation is done above
                                        echo !empty($ukuran_teks) ? trim($ukuran_teks) : 'N/A';
                                        ?>
                                    </p>
                                    <p><strong>Stok:</strong>
                                        <?php
                                        if ($produk['stok'] > 0) {
                                            echo ' Tersedia (' . htmlspecialchars($produk['stok']) . ')';
                                        } else {
                                            echo ' <span class="pre-order-status">Pre Order</span>';
                                        }
                                        ?>
                                    </p>
                                </div>

                                <!-- Add Price Section if applicable -->
                                <?php /* if (isset($produk['harga'])): ?>
                                <div class="price-section">
                                    <span class="current-price">Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?></span>
                                    <!-- Add original price / discount here -->
                                </div>
                                <?php endif; */ ?>

<div class="action-buttons">
    <?php
    // Ganti dengan nomor WhatsApp tujuan Anda (format internasional tanpa + atau 0 di depan)
    $nomor_whatsapp_admin = '6281567958549'; // Pastikan nomor ini benar

    // Siapkan pesan default (sama seperti sebelumnya)
    $nama_produk_wa = htmlspecialchars($produk['nama'] ?? 'Produk'); // Tambahkan fallback jika $produk null
    $kode_produk_wa = htmlspecialchars($produk['kode_produk'] ?? 'N/A');
    // Gunakan canonical URL jika tersedia untuk link produk
    $link_produk_wa = $canonical_url ?? get_current_url(); // Ambil URL halaman saat ini
    $pesan_wa = "Halo, saya tertarik dengan produk: {$nama_produk_wa} (Kode: {$kode_produk_wa}). Link: {$link_produk_wa} . Mohon info lebih lanjut.";
    $link_wa = "https://wa.me/{$nomor_whatsapp_admin}?text=" . urlencode($pesan_wa);

    // --- Logika Menonaktifkan Tombol Dihapus ---
    // Tombol WhatsApp ini selalu aktif untuk memungkinkan pertanyaan
    // $is_disabled = false; // Tidak perlu lagi
    // $disabled_class = ''; // Tidak perlu lagi
    // $href_attr = $link_wa; // Langsung gunakan link WA
    // $target_attr = 'target="_blank"'; // Selalu buka tab baru
    // $aria_disabled = ''; // Tidak perlu lagi
    // $tabindex = ''; // Tidak perlu lagi
    ?>
    
    <a href="<?php echo htmlspecialchars($link_wa); ?>" class="btn btn-success" target="_blank" style="background-color: #25D366; border-color: #25D366;" title="Order via WhatsApp">
        <i class="fab fa-whatsapp me-2"></i> Order Sekarang
    </a>
    
    <?php // Anda bisa menambahkan tombol lain di sini jika perlu ?>

</div>
                            </div>
                        </div>

                        <!-- Description Section -->
                        <?php if (!empty(trim($deskripsi_display))): ?>
                        <div class="product-description mb-4">
                            <h3>Deskripsi Produk</h3>
                            <p><?php echo nl2br(trim($deskripsi_display)); ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Share Section -->
                        <div class="share-section">
                            <span class="share-label">Bagikan:</span>
                            <div class="share-icons">
                                <!-- Replace # with actual dynamic share links -->
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($canonical_url); ?>" target="_blank" title="Bagikan di Facebook"><i class="fab fa-facebook-f"></i></a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($canonical_url); ?>&text=<?php echo urlencode($page_title); ?>" target="_blank" title="Bagikan di Twitter"><i class="fab fa-twitter"></i></a>
                                <a href="https://pinterest.com/pin/create/button/?url=<?php echo urlencode($canonical_url); ?>&media=<?php echo urlencode($og_image ?? ''); ?>&description=<?php echo urlencode($meta_description); ?>" target="_blank" title="Bagikan di Pinterest"><i class="fab fa-pinterest"></i></a>
                                <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($page_title . ' - ' . $canonical_url); ?>" target="_blank" title="Bagikan di WhatsApp"><i class="fab fa-whatsapp"></i></a>
                            </div>
                        </div>
                    </div>

                    <!-- Related Products Sidebar Column -->
                    <div class="col-lg-4">
                        <div class="related-products-sidebar">
                            <h3 class="related-products-title">Produk Terkait</h3>
                            <?php if (!empty($related_products)): ?>
                                <?php foreach ($related_products as $related_item):
                                    $related_folder = "../images/produk/" . htmlspecialchars($related_item['kode_produk']) . "/";
                                    $related_image_path = $related_folder . htmlspecialchars($related_item['foto']);
                                    $related_image_exists = !empty($related_item['foto']) && file_exists($related_image_path);
                                ?>
                                <div class="related-product-card mb-3"> <!-- Added margin bottom -->
                                    <a href="detail-produk.php?id=<?php echo $related_item['id']; ?>">
                                        <div class="related-product-image-container">
                                            <?php if ($related_image_exists): ?>
                                                <img src="<?php echo htmlspecialchars($related_image_path); ?>" alt="<?php echo htmlspecialchars($related_item['nama']); ?>" class="related-product-image">
                                            <?php else: ?>
                                                <div class="no-image-placeholder related">Gambar Tidak Tersedia</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="related-product-info">
                                            <p class="related-product-name"><?php echo htmlspecialchars($related_item['nama']); ?></p>
                                            <!-- Optionally add price or other details here -->
                                        </div>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-center text-muted mt-3">Tidak terdapat produk terkait.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div> <!-- End .row -->

            <?php endif; // End check if $produk exists ?>
        </div> <!-- End .container-xl -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const mainImage = document.getElementById('mainImage');
        const thumbnails = document.querySelectorAll('.thumbnail');

        function changeImage(src, clickedElement) {
            if (mainImage) {
                mainImage.src = src;
                // Update active state on thumbnails
                thumbnails.forEach(thumb => {
                    thumb.classList.remove('active');
                });
                if (clickedElement) {
                    clickedElement.classList.add('active');
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Add to cart button logic (No changes needed for SEO)
            const addToCartButton = document.querySelector('.add-to-cart-btn');
            if (addToCartButton) {
                addToCartButton.addEventListener('click', () => {
                    if (!addToCartButton.disabled) {
                        // const quantityInput = document.getElementById('quantity'); // Quantity input seems removed
                        // const quantity = quantityInput ? quantityInput.value : 1;
                        const quantity = 1; // Assume quantity 1 if input is removed
                        alert(`Produk ditambahkan ke keranjang! Jumlah: ${quantity}`);
                        // TODO: Add actual AJAX logic to add to cart
                    }
                });
            }

            // Buy now button logic (No changes needed for SEO)
            const buyNowButton = document.querySelector('.buy-now-btn');
            if (buyNowButton) {
                buyNowButton.addEventListener('click', () => {
                     if (!buyNowButton.disabled) {
                        // const quantityInput = document.getElementById('quantity'); // Quantity input seems removed
                        // const quantity = quantityInput ? quantityInput.value : 1;
                        const quantity = 1; // Assume quantity 1 if input is removed
                        alert(`Fitur Beli Sekarang belum diimplementasikan. Jumlah: ${quantity}`);
                        // TODO: Add logic for buy now process
                     }
                });
            }

            // Quantity input logic (No changes needed for SEO, but input seems removed)
            // const quantityInput = document.getElementById('quantity');
            // if (quantityInput && !quantityInput.disabled) {
            //     const maxStock = parseInt(quantityInput.max, 10);
            //     quantityInput.addEventListener('change', () => {
            //         let value = parseInt(quantityInput.value, 10);
            //         if (isNaN(value) || value < 1) {
            //             quantityInput.value = 1;
            //         } else if (value > maxStock) {
            //             quantityInput.value = maxStock;
            //         }
            //     });
            // }
        });
    </script>
     <?php include 'templat/footer.php'; ?>
</body>
</html>