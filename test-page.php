<?php
/*
Template Name: Social Media QR Generator
*/

get_header();
?>

<div class="wrap">

<!-- Generator Form Section -->
<div id="formSection">
    <h2>Social Media QR Generator</h2>

    <form id="socialMediaForm">
        <label>Title:</label>
        <input type="text" id="titleInput" placeholder="Enter title"><br><br>

        <label>Message:</label>
        <input type="text" id="messageInput" placeholder="Enter message"><br><br>

        <label>Subtext:</label>
        <input type="text" id="subtextInput" placeholder="Enter subtext"><br><br>

        <label>Background Color:</label>
        <input type="color" id="bgColor" value="#ffffff"><br><br>

        <button type="submit">Generate QR Code</button>
    </form>

    <div class="qrcode" style="margin-top:20px;">
        <canvas id="qrcodeCanvas"></canvas>
    </div>

    <div id="livePreview" class="card" style="margin-top:20px; border:1px solid #ccc; padding:15px; width:300px; background:#fff;">
        <h3 id="previewTitle"></h3>
        <p id="previewMessage"></p>
        <p id="previewSubtext"></p>
    </div>
</div>

<!-- Preview Section (for QR scan only) -->
<div id="previewSection" class="card" style="display:none; margin-top:20px; border:1px solid #ccc; padding:15px; width:300px; background:#fff;">
    <h3 id="previewOnlyTitle"></h3>
    <p id="previewOnlyMessage"></p>
    <p id="previewOnlySubtext"></p>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
<script>
// Decode JSON data from URL hash
function decodeDataFromHash(){
    if(location.hash.length > 1){
        try {
            return JSON.parse(decodeURIComponent(location.hash.substring(1)));
        } catch(e){ return null; }
    }
    return null;
}

// Render preview-only mode (hide everything else)
function renderPreviewOnly(data){
    if(!data) return;
    // Hide all main page content (generator, header, footer)
    document.body.innerHTML = '';
    // Create a new div to show only the preview card
    const previewDiv = document.createElement('div');
    previewDiv.style.display = 'flex';
    previewDiv.style.justifyContent = 'center';
    previewDiv.style.alignItems = 'center';
    previewDiv.style.height = '100vh';
    previewDiv.innerHTML = `
        <div class="card" style="border:1px solid #ccc; padding:20px; width:300px; background:${data.bgColor || '#fff'}; text-align:center;">
            <h3>${data.title || ''}</h3>
            <p>${data.message || ''}</p>
            <p>${data.subtext || ''}</p>
        </div>
    `;
    document.body.appendChild(previewDiv);
}

// On page load, check if hash exists (QR scan)
window.addEventListener('load', function(){
    const hashData = decodeDataFromHash();
    if(hashData){
        renderPreviewOnly(hashData);
    }
});

// Generator form logic
document.getElementById("socialMediaForm").addEventListener("submit", function(e){
    e.preventDefault();

    const data = {
        title: document.getElementById("titleInput").value,
        message: document.getElementById("messageInput").value,
        subtext: document.getElementById("subtextInput").value,
        bgColor: document.getElementById("bgColor").value
    };

    // Update live preview
    const livePreview = document.getElementById("livePreview");
    livePreview.style.backgroundColor = data.bgColor;
    document.getElementById("previewTitle").textContent = data.title;
    document.getElementById("previewMessage").textContent = data.message;
    document.getElementById("previewSubtext").textContent = data.subtext;

    // Encode data as URL hash
    const encodedData = encodeURIComponent(JSON.stringify(data));

    // Use current page URL
    const pageUrl = window.location.href.split('#')[0];
    const qrUrl = pageUrl + '#' + encodedData;

    // Generate QR code
    QRCode.toCanvas(document.getElementById('qrcodeCanvas'), qrUrl, {width:250}, function(err){
        if(err) console.error(err);
    });
});
</script>

<?php get_footer(); ?>
