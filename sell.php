<?php
// sell.php
include 'connection.php'; // PDO connection

$uploadStatus = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Handle uploaded images
    $uploadedFiles = [];
    if (!empty($_FILES['images']['name'][0])) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            $fileName = time() . '_' . basename($_FILES['images']['name'][$key]);
            $targetFile = $uploadDir . $fileName;

            if (move_uploaded_file($tmpName, $targetFile)) {
                $uploadedFiles[] = $targetFile;
            }
        }
    }

    // Form data
    $category = $_POST['category'] ?? '';
    $brand = $_POST['brand'] ?? '';
    $name = $_POST['product_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $condition = $_POST['condition'] ?? '';
    $usage = $_POST['usage'] ?? '';
    $warranty = isset($_POST['warranty']) ? 1 : 0;
    $overclocked = isset($_POST['overclocked']) ? 1 : 0;
    $originalBox = isset($_POST['box']) ? 1 : 0;

    // Specs as JSON
    $specsArray = $_POST['specs'] ?? [];
    $specsJson = json_encode($specsArray);
    $imagesJson = json_encode($uploadedFiles);

    // Insert
    $stmt = $pdo->prepare("
        INSERT INTO products 
        (category_id, brand_id, name, description, price, `condition`, usage_duration, warranty_remaining, never_overclocked, original_box_included, status, listing_created_at, specifications, images)
        VALUES
        (:category, :brand, :name, :description, :price, :condition, :usage, :warranty, :overclocked, :originalBox, 'active', NOW(), :specs, :images)
    ");

    try {
        $stmt->execute([
            ':category' => $category,
            ':brand' => $brand,
            ':name' => $name,
            ':description' => $description,
            ':price' => $price,
            ':condition' => $condition,
            ':usage' => $usage,
            ':warranty' => $warranty,
            ':overclocked' => $overclocked,
            ':originalBox' => $originalBox,
            ':specs' => $specsJson,
            ':images' => $imagesJson
        ]);
        $uploadStatus = "success";
    } catch (PDOException $e) {
        $uploadStatus = "error: " . $e->getMessage();
    }
}

// Fetch categories and brands
$categories = $pdo->query("SELECT category_id, name FROM categories ORDER BY category_id")->fetchAll(PDO::FETCH_ASSOC);
$brands = $pdo->query("SELECT brand_id, name FROM brands ORDER BY brand_id")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sell Your PC Part - NextRig</title>
<link rel="stylesheet" href="style.css">
<script src="https://kit.fontawesome.com/4a24449835.js" crossorigin="anonymous"></script>
<style>
.image-preview { display:flex; gap:10px; margin-top:10px; }
.image-preview img { width:80px; height:80px; object-fit:cover; border:1px solid #ccc; padding:2px; }
.preview-card { border:1px solid #ccc; padding:10px; margin-top:20px; }
</style>
</head>
<body>

<header class="header">
<!-- navbar unchanged -->
</header>

<main class="container">
<div class="page-header">
<h1>Sell Your PC Part</h1>
<span class="header-note"><i class="fas fa-info-circle"></i> Fill in all details to get the best price</span>
</div>

<?php if ($uploadStatus === "success"): ?>
<div class="success-banner">Listing submitted successfully!</div>
<?php elseif (str_starts_with($uploadStatus, "error")): ?>
<div class="error-banner"><?= htmlspecialchars($uploadStatus) ?></div>
<?php endif; ?>

<div class="sell-layout">
<section class="sell-form">
<form method="POST" enctype="multipart/form-data">
    <!-- Images -->
    <div class="form-section">
        <h2>Product Images</h2>
        <div class="image-uploader" id="previewContainer">
            <div id="addPhotoBox" class="add-photo-box">
                <i class="fas fa-plus"></i><span>Add Photo</span>
            </div>
        </div>
        <input type="file" id="imageUpload" accept="image/*" multiple name="images[]">
        <div class="image-preview" id="imagePreview"></div>
        <p class="form-hint">Upload up to 8 photos. First photo will be your main image.</p>
    </div>

    <!-- Product Details -->
    <div class="form-section">
        <h2>Product Details</h2>
        <div class="form-row">
            <div class="form-group">
                <label for="category">Product Category</label>
                <select id="category" name="category">
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['category_id']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="brand">Brand</label>
                <select id="brand" name="brand">
                    <?php foreach($brands as $brand): ?>
                        <option value="<?= htmlspecialchars($brand['brand_id']) ?>"><?= htmlspecialchars($brand['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="product-name">Product Name</label>
            <input type="text" id="product-name" name="product_name" placeholder="e.g., NVIDIA RTX 3070">
        </div>
        <div class="form-group">
            <label for="description">Product Description</label>
            <textarea id="description" name="description" placeholder="Describe your product..."></textarea>
        </div>
        <div class="form-group">
            <label for="price">Price ($)</label>
            <input type="number" id="price" name="price" placeholder="Enter your selling price" required>
        </div>
    </div>

    <!-- Dynamic Specs -->
    <div class="form-section">
        <h2>Specifications</h2>
        <div id="specsContainer"></div>
    </div>

    <!-- Condition & Usage -->
    <div class="form-section">
        <h2>Condition & Usage</h2>
        <div class="form-row">
            <div class="form-group">
                <label for="condition">Condition</label>
                <select id="condition" name="condition">
                    <option value="">Select Condition</option>
                    <option>New</option>
                    <option>Used - Like New</option>
                    <option>Used - Good</option>
                    <option>Used - Acceptable</option>
                </select>
            </div>
            <div class="form-group">
                <label for="usage">Usage Duration</label>
                <select id="usage" name="usage">
                    <option value="">Select Usage</option>
                    <option>0-6 months</option>
                    <option>6-12 months</option>
                    <option>1-2 years</option>
                    <option>2+ years</option>
                </select>
            </div>
        </div>
        <div class="checkbox-group">
            <div><input type="checkbox" id="box" name="box"><label for="box">Original box included</label></div>
            <div><input type="checkbox" id="warranty" name="warranty"><label for="warranty">Warranty remaining</label></div>
            <div><input type="checkbox" id="overclocked" name="overclocked"><label for="overclocked">Never overclocked</label></div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Submit Listing</button>
</form>
</section>
</div>
</main>

<script>
const categorySpecs = {
    "Motherboards": ["Model Number", "Form Factor", "Chipset", "Socket Type", "Memory Slots"],
    "Graphics Cards": ["Model Number", "Memory Size", "Interface", "Power Consumption"],
    "Processors": ["Model Number", "Cores", "Threads", "Base Clock", "Boost Clock"],
    "Memory (RAM)": ["Model Number", "Memory Size", "Type", "Speed"],
    "Storage": ["Model Number", "Capacity", "Type", "Interface"],
    "Power Supplies (PSU)": ["Model Number", "Wattage", "Efficiency Rating", "Modular"],
    "Cabinet": ["Model Number", "Form Factor", "Color", "Material"]
};

const categorySelect = document.getElementById('category');
const specsContainer = document.getElementById('specsContainer');

function createSpecField(specName){
    const div = document.createElement('div');
    div.className = "form-group";
    const label = document.createElement('label');
    label.textContent = specName;
    const input = document.createElement('input');
    input.type = "text";
    input.name = `specs[${specName.replace(/\s+/g,'_').toLowerCase()}]`; // <- important
    input.placeholder = `Enter ${specName}`;
    div.appendChild(label);
    div.appendChild(input);
    return div;
}

function updateSpecFields(){
    specsContainer.innerHTML = '';
    const selectedCategory = categorySelect.options[categorySelect.selectedIndex].text;
    if(categorySpecs[selectedCategory]){
        categorySpecs[selectedCategory].forEach(spec => {
            specsContainer.appendChild(createSpecField(spec));
        });
    }
}

const imageInput = document.getElementById('imageUpload');
const previewContainer = document.getElementById('previewContainer');
const addPhotoBox = document.getElementById('addPhotoBox');
const maxImages = 8;

addPhotoBox.addEventListener('click', () => imageInput.click());

imageInput.addEventListener('change', async function(e) {
    const files = Array.from(e.target.files).slice(0, maxImages); // enforce max 8
    previewContainer.innerHTML = '';
    previewContainer.appendChild(addPhotoBox);

    if(files.length === 0) return;

    const resizedFiles = [];

    // Main image container
    const mainImgDiv = document.createElement('div');
    mainImgDiv.className = 'main-image-preview';
    mainImgDiv.style.marginBottom = '10px';
    previewContainer.appendChild(mainImgDiv);

    // Thumbnails container
    const thumbContainer = document.createElement('div');
    thumbContainer.className = 'image-preview';
    previewContainer.appendChild(thumbContainer);

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const img = await loadImage(file);

        // Crop proportionally for 1200x900
        const targetWidth = 1200;
        const targetHeight = 900;
        let srcX = 0, srcY = 0, srcW = img.width, srcH = img.height;
        const srcRatio = img.width / img.height;
        const targetRatio = targetWidth / targetHeight;

        if(srcRatio > targetRatio){
            srcW = img.height * targetRatio;
            srcX = (img.width - srcW)/2;
        } else {
            srcH = img.width / targetRatio;
            srcY = (img.height - srcH)/2;
        }

        // Draw on canvas at 1200x900
        const canvas = document.createElement('canvas');
        canvas.width = targetWidth;
        canvas.height = targetHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, srcX, srcY, srcW, srcH, 0, 0, targetWidth, targetHeight);

        // Preview image (smaller for page display)
        const previewImg = document.createElement('img');
        previewImg.src = canvas.toDataURL('image/jpeg', 0.9);
        if(i === 0){
            previewImg.style.width = '300px'; // small preview on page
            previewImg.style.height = 'auto';
            mainImgDiv.appendChild(previewImg);
        } else {
            previewImg.style.width = '80px';
            previewImg.style.height = '80px';
            previewImg.style.objectFit = 'cover';
            thumbContainer.appendChild(previewImg);
        }

        // Save resized file (1200x900) for upload
        const blob = await new Promise(resolve => canvas.toBlob(resolve,'image/jpeg',0.9));
        resizedFiles.push(new File([blob], file.name, { type: 'image/jpeg' }));
    }

    // Replace original files with resized ones
    const dataTransfer = new DataTransfer();
    resizedFiles.forEach(f => dataTransfer.items.add(f));
    imageInput.files = dataTransfer.files;
});

function loadImage(file) {
    return new Promise(resolve => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.src = URL.createObjectURL(file);
    });
}


categorySelect.addEventListener('change', updateSpecFields);
updateSpecFields(); // initialize on load
</script>
</body>
</html>