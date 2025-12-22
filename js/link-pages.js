jQuery(function ($) {
  // Function to perform validation on a given step container
  function validateStep(stepId) {
    let isValid = true;
    const $step = $('#' + stepId);

    // Clear previous errors
    $step.find('.validation-error').removeClass('validation-error');
    $step.find('.validation-error-border').removeClass('validation-error-border');
    $step.find('.error-message').remove();

    // 1. Check native HTML5 'required' attribute on standard inputs
    $step.find('input:required, textarea:required, select:required').each(function () {
      const $input = $(this);
      const value = $input.val().trim();

      // Skip hidden inputs (often used for color pickers, which are non-visual validation)
      if ($input.attr('type') === 'hidden') {
        return true;
      }

      if (value === "") {
        isValid = false;
        $input.addClass('validation-error');
        // Check if a basic error message is visible near the input
        if ($input.next('.error-message').length === 0) {
          $input.after('<span class="error-message" style="color:red; font-size: 12px; display:block; margin-top: 3px;">This field is required.</span>');
        }
      } else {
        $input.removeClass('validation-error');
      }

      // Example: Specific URL validation for text fields marked as required
      if ($input.attr('type') === 'url' || $input.attr('name').includes('url')) {
        // Simple URL validation regex (can be made more robust if needed)
        const urlPattern = new RegExp('^(https?:\\/\\/)?' + // protocol (optional)
          '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)*[a-z\\d]([a-z\\d-]*[a-z\\d])*|' + // domain name
          '((\\d{1,3}\\.){3}\\d{1,3}))' + // OR ip (v4) address
          '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // port and path
          '(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
          '(\\#[-a-z\\d_]*)?$', 'i'); // fragment locator

        if (!urlPattern.test(value)) {
          isValid = false;
          $input.addClass('validation-error');
          if ($input.next('.error-message').text() !== 'Please enter a valid URL.') {
            $input.next('.error-message').remove(); // Remove previous error if any
            $input.after('<span class="error-message" style="color:red; font-size: 12px; display:block; margin-top: 3px;">Please enter a valid URL.</span>');
          }
        } else {
          $input.removeClass('validation-error');
          $input.next('.error-message').remove();
        }
      }
    });

    // 2. Custom Validation for non-standard fields (e.g., File Upload Area)
    // Assuming Step 1 contains the profile/cover image upload fields.
    const $coverUpload = $step.find('#drop-area-layout, #drop-area-profile');
    if ($coverUpload.length && $coverUpload.data('is-required')) { // Check a custom data attribute if file is required

      // Find the associated hidden input that stores the file URL
      const $hiddenInput = $coverUpload.find('input[type="hidden"].lp-profile-cover');

      // Check if the hidden input is empty (meaning no image uploaded/selected)
      if ($hiddenInput.length && $hiddenInput.val().trim() === '') {
        isValid = false;
        $coverUpload.addClass('validation-error-border');
        if ($coverUpload.find('.error-message').length === 0) {
          $coverUpload.append('<span class="error-message" style="color:red; font-size: 12px; display:block; margin-top:5px;">An image is required.</span>');
        }
      } else {
        $coverUpload.removeClass('validation-error-border');
        $coverUpload.find('.error-message').remove();
      }
    }

    // If validation failed, scroll to the first error element for user visibility
    if (!isValid) {
      $('html, body').animate({
        scrollTop: $step.find('.validation-error:first, .validation-error-border:first').offset().top - 50
      }, 500);
    }

    return isValid;
  }
  function initDropArea(dropArea) {

    if (!dropArea) return;

    const fileInput = dropArea.querySelector(".upload-input");
    const previewBox = dropArea.querySelector(".cover-preview");
    const previewImg = dropArea.querySelector(".preview-img");
    const removeBtn = dropArea.querySelector(".remove-btn");
    const placeholder = dropArea.querySelector(".upload-placeholder");

    // If widget does NOT contain uploader, skip
    if (!fileInput || !previewImg || !previewBox) return;

    // Highlight on drag
    function highlight(e) {
      e.preventDefault();
      e.stopPropagation();
      dropArea.classList.add("highlight");
    }

    // Remove highlight
    function unhighlight(e) {
      e.preventDefault();
      e.stopPropagation();
      dropArea.classList.remove("highlight");
    }

    // Handle dropped files
    function handleDrop(e) {
      e.preventDefault();
      let dt = e.dataTransfer;
      let file = dt.files[0];
      processFile(file);
    }

    // Validations + Preview
    function processFile(file) {

      if (!file.type.startsWith("image/")) {
        alert("Only image files are allowed!");
        return;
      }

      if (file.size > 5 * 1024 * 1024) {
        alert("File must be less than 5MB");
        return;
      }
      debugger;
      let reader = new FileReader();
      reader.onload = function (e) {

        previewImg.src = e.target.result;
        previewImg.style.height = '64px';
        previewImg.style.objectFit = 'contain';
        previewImg.style.paddingBottom = '10px';

        previewBox.style.display = "block";
        placeholder.style.display = "none";

        // Get widget ID
        const widgetId = $(dropArea).closest(".widgetbox").attr("id");

        const $imgPreview = $(
          `.mobilelayoutmain #linkpage-id .btn[data-preview-for="${widgetId}"] .icon-preview img, 
                 .mobilelayoutmain #linkpage-id .btn[data-preview-for="${widgetId}"] .thumbnail-preview img`
        );

        // Update both images
        $imgPreview.attr("src", e.target.result);
      };

      reader.readAsDataURL(file);
    }

    // Remove preview
    removeBtn?.addEventListener("click", () => {

      previewImg.src = "";
      previewBox.style.display = "none";
      placeholder.style.display = "block";
      fileInput.value = "";

      const widgetId = $(dropArea).closest(".widgetbox").attr("id");

      const $imgPreview = $(
        `.mobilelayoutmain #linkpage-id .btn[data-preview-for="${widgetId}"] .icon-preview img, 
             .mobilelayoutmain #linkpage-id .btn[data-preview-for="${widgetId}"] .thumbnail-preview img`
      );

      $imgPreview.each(function () {
        const defaultSrc = $(this).attr("data-default-src");
        $(this).attr("src", defaultSrc);
      });

    });

    // Click → Upload
    // fileInput.addEventListener("change", function () {
    //   if (this.files.length > 0) {
    //     processFile(this.files[0]);
    //   }
    // });

    fileInput.addEventListener("change", function (e) {
      // Use e.target.files instead of this.files to ensure we're getting the right input
      if (e.target.files && e.target.files.length > 0) {
        processFile(e.target.files[0]);
      }
    });

    // Drag events
    ["dragenter", "dragover"].forEach(eventName =>
      dropArea.addEventListener(eventName, highlight)
    );

    ["dragleave", "drop"].forEach(eventName =>
      dropArea.addEventListener(eventName, unhighlight)
    );

    dropArea.addEventListener("drop", handleDrop);
  }
  function initDropAreaLayout() {

    const dropArea = document.getElementById("drop-area-layout");
    const fileInput = dropArea.querySelector(".upload-input");
    const previewBox = dropArea.querySelector(".cover-preview");
    const previewImg = dropArea.querySelector(".preview-img");
    const removeBtn = dropArea.querySelector(".remove-btn");
    const placeholder = dropArea.querySelector(".upload-placeholder");

    // If widget does NOT contain uploader, skip
    if (!fileInput || !previewImg || !previewBox) return;

    // Highlight on drag
    function highlight(e) {
      e.preventDefault();
      e.stopPropagation();
      dropArea.classList.add("highlight");
    }

    // Remove highlight
    function unhighlight(e) {
      e.preventDefault();
      e.stopPropagation();
      dropArea.classList.remove("highlight");
    }

    // Handle dropped files
    function handleDrop(e) {
      e.preventDefault();
      let dt = e.dataTransfer;
      let file = dt.files[0];
      processFile(file);
    }

    // Validations + Preview
    function processFile(file) {

      if (!file.type.startsWith("image/")) {
        alert("Only image files are allowed!");
        return;
      }

      if (file.size > 5 * 1024 * 1024) {
        alert("File must be less than 5MB");
        return;
      }

      let reader = new FileReader();

      reader.onload = function (e) {

        previewImg.src = e.target.result;
        previewImg.style.height = '64px';
        previewImg.style.objectFit = 'contain';
        previewImg.style.paddingBottom = '10px';

        previewBox.style.display = "block";
        placeholder.style.display = "none";

        // Get widget ID
        const imageUrl = e.target.result;
        const widgetId = $(dropArea).closest(".widgetbox").attr("id");
        const styleId = `header-bg-style-section`;

        // Remove existing style if present
        $(`#${styleId}`).remove();

        // Add new style tag
        $('head').append(`
                <style id="${styleId}">
                    .linkpage-header::before,
                    .linkpage-header-full::before {
                        background-image: url('${imageUrl}') !important;
                    }
                </style>
            `);
        // const $imgPreview = $(
        //     `.mobilelayoutmain #linkpage-id .btn[data-preview-for="${widgetId}"] .icon-preview img, 
        //      .mobilelayoutmain #linkpage-id .btn[data-preview-for="${widgetId}"] .thumbnail-preview img`
        // );

        // // Update both images
        // $imgPreview.attr("src", e.target.result);
      };

      reader.readAsDataURL(file);
    }

    // Remove preview
    removeBtn?.addEventListener("click", () => {

      previewImg.src = "";
      previewBox.style.display = "none";
      placeholder.style.display = "block";
      fileInput.value = "";

      // const widgetId = $(dropArea).closest(".widgetbox").attr("id");
      const styleId = `header-bg-style-section`;

      // Remove the background-image style
      $(`#${styleId}`).remove();
      // const $imgPreview = $(
      //     `.mobilelayoutmain #linkpage-id .btn[data-preview-for="${widgetId}"] .icon-preview img, 
      //      .mobilelayoutmain #linkpage-id .btn[data-preview-for="${widgetId}"] .thumbnail-preview img`
      // );

      // $imgPreview.each(function () {
      //     const defaultSrc = $(this).attr("data-default-src");
      //     $(this).attr("src", defaultSrc);
      // });

    });

    // Click → Upload
    fileInput.addEventListener("change", function () {
      if (this.files.length > 0) {
        processFile(this.files[0]);
      }
    });

    // Drag events
    ["dragenter", "dragover"].forEach(eventName =>
      dropArea.addEventListener(eventName, highlight)
    );

    ["dragleave", "drop"].forEach(eventName =>
      dropArea.addEventListener(eventName, unhighlight)
    );

    dropArea.addEventListener("drop", handleDrop);
  }
  function initDropAreaProfile() {

    const dropArea = document.getElementById("drop-area-profile");
    const fileInput = dropArea.querySelector(".upload-input");
    const previewBox = dropArea.querySelector(".cover-preview");
    const previewImg = dropArea.querySelector(".preview-img");
    const removeBtn = dropArea.querySelector(".remove-btn");
    const placeholder = dropArea.querySelector(".upload-placeholder");

    // If widget does NOT contain uploader, skip
    if (!fileInput || !previewImg || !previewBox) return;

    // Highlight on drag
    function highlight(e) {
      e.preventDefault();
      e.stopPropagation();
      dropArea.classList.add("highlight");
    }

    // Remove highlight
    function unhighlight(e) {
      e.preventDefault();
      e.stopPropagation();
      dropArea.classList.remove("highlight");
    }

    // Handle dropped files
    function handleDrop(e) {
      e.preventDefault();
      let dt = e.dataTransfer;
      let file = dt.files[0];
      processFile(file);
    }

    // Validations + Preview
    function processFile(file) {

      if (!file.type.startsWith("image/")) {
        alert("Only image files are allowed!");
        return;
      }

      if (file.size > 5 * 1024 * 1024) {
        alert("File must be less than 5MB");
        return;
      }

      let reader = new FileReader();

      reader.onload = function (e) {

        previewImg.src = e.target.result;
        previewImg.style.height = '64px';
        previewImg.style.objectFit = 'contain';
        previewImg.style.paddingBottom = '10px';

        previewBox.style.display = "block";
        placeholder.style.display = "none";

        // Get widget ID
        const imageUrl = e.target.result;
        const widgetId = $(dropArea).closest(".widgetbox").attr("id");
        const styleId = `header-bg-style-section`;

        // Remove existing style if present
        // $(`#${styleId}`).remove();

        // Add new style tag
        $('.lp-thumbnail-preview').attr("src", imageUrl);
        var thumbnailPreview = document.querySelector('#linkpage-header-id img.img-box');
        thumbnailPreview.src = imageUrl;
        // $('head').append(`
        //         <style id="${styleId}">
        //             .linkpage-header::before,
        //             .linkpage-header-full::before {
        //                 background-image: url('${imageUrl}') !important;
        //             }
        //         </style>
        //     `);
        // const $imgPreview = $(
        //     `.mobilelayoutmain #linkpage-id .btn[data-preview-for="${widgetId}"] .icon-preview img, 
        //      .mobilelayoutmain #linkpage-id .btn[data-preview-for="${widgetId}"] .thumbnail-preview img`
        // );

        // // Update both images
        // $imgPreview.attr("src", e.target.result);
      };

      reader.readAsDataURL(file);
    }

    // Remove preview
    removeBtn?.addEventListener("click", () => {

      previewImg.src = "";
      previewBox.style.display = "none";
      placeholder.style.display = "block";
      fileInput.value = "";

      // const widgetId = $(dropArea).closest(".widgetbox").attr("id");
      const styleId = `header-bg-style-section`;

      // Remove the background-image style
      // $(`#${styleId}`).remove();

      // const $imgPreview = $(
      //     `.mobilelayoutmain #linkpage-id .btn[data-preview-for="${widgetId}"] .icon-preview img, 
      //      .mobilelayoutmain #linkpage-id .btn[data-preview-for="${widgetId}"] .thumbnail-preview img`
      // );
      $('.lp-thumbnail-preview').attr("src", $('.lp-thumbnail-preview').data('default-url'));
      var thumbnailPreview = document.querySelector('#linkpage-header-id img.img-box');
      var defaultSrc = thumbnailPreview.getAttribute('data-default-url');
      thumbnailPreview.src = defaultSrc;
      // $imgPreview.each(function () {
      //     const defaultSrc = $(this).attr("data-default-src");
      //     $(this).attr("src", defaultSrc);
      // });

    });

    // Click → Upload
    fileInput.addEventListener("change", function () {
      if (this.files.length > 0) {
        processFile(this.files[0]);
      }
    });

    // Drag events
    ["dragenter", "dragover"].forEach(eventName =>
      dropArea.addEventListener(eventName, highlight)
    );

    ["dragleave", "drop"].forEach(eventName =>
      dropArea.addEventListener(eventName, unhighlight)
    );

    dropArea.addEventListener("drop", handleDrop);
  }

  function initPDFDropArea(dropArea) {

    if (!dropArea) return;

    const fileInput = dropArea.querySelector(".upload-input");
    const previewBox = dropArea.querySelector(".cover-preview");
    const previewImg = dropArea.querySelector(".preview-img");
    const removeBtn = dropArea.querySelector(".remove-btn");
    const placeholder = dropArea.querySelector(".upload-placeholder");

    // If widget does NOT contain uploader, skip
    if (!fileInput || !previewImg || !previewBox) return;

    // Highlight on drag
    function highlight(e) {
      e.preventDefault();
      e.stopPropagation();
      dropArea.classList.add("highlight");
    }

    // Remove highlight
    function unhighlight(e) {
      e.preventDefault();
      e.stopPropagation();
      dropArea.classList.remove("highlight");
    }

    // Handle dropped files
    function handleDrop(e) {
      e.preventDefault();
      let dt = e.dataTransfer;
      let file = dt.files[0];
      processFile(file);
    }

    // Validations + Preview
    function processFile(file) {

      // Allow only PDF and DOC files
      const allowedTypes = [
        "application/pdf",
        "application/msword",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
      ];

      if (!allowedTypes.includes(file.type)) {
        alert("Only PDF and DOC/DOCX files are allowed!");
        return;
      }

      // Check for 50MB limit
      if (file.size > 50 * 1024 * 1024) {
        alert("File must be less than 50MB");
        return;
      }

      let reader = new FileReader();

      reader.onload = function (e) {

        // Show file icon instead of image preview
        // You can customize this based on file type
        if (file.type === "application/pdf") {
          previewImg.src = "https://dashboard.uniqode.com/assets/img/linkpage/pdf-upload.svg"; // Add your PDF icon path
        } else {
          previewImg.src = "https://dashboard.uniqode.com/assets/img/linkpage/pdf-upload.svg"; // Add your DOC icon path
        }

        previewImg.style.height = '64px';
        previewImg.style.objectFit = 'contain';
        previewImg.style.paddingBottom = '10px';

        previewBox.style.display = "block";
        placeholder.style.display = "none";

        // Get widget ID
        // const widgetId = $(dropArea).closest(".widgetbox").attr("id");

        // const $imgPreview = $(
        //   `.mobilelayoutmain #linkpage-id .btn[data-preview-for="${widgetId}"] .icon-preview img, 
        //          .mobilelayoutmain #linkpage-id .btn[data-preview-for="${widgetId}"] .thumbnail-preview img`
        // );

        // // Update both images
        // $imgPreview.attr("src", e.target.result);
      };

      reader.readAsDataURL(file);
    }

    // Remove preview
    removeBtn?.addEventListener("click", () => {

      previewImg.src = "";
      previewBox.style.display = "none";
      placeholder.style.display = "block";
      fileInput.value = "";

      // const widgetId = $(dropArea).closest(".widgetbox").attr("id");

      // const $imgPreview = $(
      //   `.mobilelayoutmain #linkpage-id .btn[data-preview-for="${widgetId}"] .icon-preview img, 
      //        .mobilelayoutmain #linkpage-id .btn[data-preview-for="${widgetId}"] .thumbnail-preview img`
      // );

      // $imgPreview.each(function () {
      //   const defaultSrc = $(this).attr("data-default-src");
      //   $(this).attr("src", defaultSrc);
      // });

    });

    // Click → Upload
    fileInput.addEventListener("change", function () {
      if (this.files.length > 0) {
        processFile(this.files[0]);
      }
    });

    // Drag events
    ["dragenter", "dragover"].forEach(eventName =>
      dropArea.addEventListener(eventName, highlight)
    );

    ["dragleave", "drop"].forEach(eventName =>
      dropArea.addEventListener(eventName, unhighlight)
    );

    dropArea.addEventListener("drop", handleDrop);
  }

  function initImageGalleryUpload(galleryArea) {

    if (!galleryArea) return;

    const fileInput = galleryArea.querySelector(".upload-input");
    const previewContainer = galleryArea.querySelector(".cover-preview");
    const placeholder = galleryArea.querySelector(".upload-placeholder");

    // If widget does NOT contain uploader, skip
    if (!fileInput || !previewContainer) return;

    // Make input accept multiple files
    fileInput.setAttribute('multiple', 'multiple');

    // Store uploaded images
    let uploadedImages = [];

    // Get widget ID for preview updates
    const widgetId = $(galleryArea).closest(".widgetbox").attr("id");

    // Highlight on drag
    function highlight(e) {
      e.preventDefault();
      e.stopPropagation();
      galleryArea.classList.add("highlight");
    }

    // Remove highlight
    function unhighlight(e) {
      e.preventDefault();
      e.stopPropagation();
      galleryArea.classList.remove("highlight");
    }

    // Handle dropped files
    function handleDrop(e) {
      e.preventDefault();
      let dt = e.dataTransfer;
      let files = Array.from(dt.files);
      processFiles(files);
    }

    // Validations + Preview for multiple files
    function processFiles(files) {

      files.forEach(file => {

        // Validate image type
        if (!file.type.startsWith("image/")) {
          alert(`"${file.name}" is not an image file!`);
          return;
        }

        // Validate file size (5MB per file)
        if (file.size > 5 * 1024 * 1024) {
          alert(`"${file.name}" exceeds 5MB limit!`);
          return;
        }

        // Check for duplicates
        const isDuplicate = uploadedImages.some(img =>
          img.name === file.name && img.size === file.size
        );

        if (isDuplicate) {
          alert(`"${file.name}" is already uploaded!`);
          return;
        }

        let reader = new FileReader();

        reader.onload = function (e) {

          // Create unique ID for this image
          const imageId = `img_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;

          // Store image data
          uploadedImages.push({
            id: imageId,
            name: file.name,
            size: file.size,
            src: e.target.result,
            file: file
          });

          // Create preview element in upload area
          createPreviewItem(imageId, e.target.result, file.name);

          // Update mobile preview (grid and carousel)
          updateMobilePreview();

          // Hide placeholder and show preview container
          if (uploadedImages.length > 0) {
            placeholder.style.display = "none";
            previewContainer.style.display = "flex";
            previewContainer.style.flexWrap = "wrap";
            previewContainer.style.gap = "10px";
            previewContainer.style.padding = "15px";
          }
        };

        reader.readAsDataURL(file);
      });
    }

    // Create preview item with remove button
    function createPreviewItem(imageId, imageSrc, fileName) {

      const previewItem = document.createElement("div");
      previewItem.className = "gallery-preview-item";
      previewItem.setAttribute("data-image-id", imageId);
      previewItem.style.cssText = `
        position: relative;
        display: inline-block;
        border: 2px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        background: #f9f9f9;
        width: 120px;
      `;

      const img = document.createElement("img");
      img.src = imageSrc;
      img.alt = fileName;
      img.className = "preview-img";
      img.style.cssText = `
        width: 120px;
        height: 120px;
        object-fit: cover;
        display: block;
      `;

      const removeBtn = document.createElement("button");
      removeBtn.type = "button";
      removeBtn.className = "remove-btn";
      removeBtn.innerHTML = "×";
      removeBtn.style.cssText = `
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(255, 0, 0, 0.8);
        color: white;
        border: none;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        font-size: 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        font-weight: bold;
        padding: 0;
      `;

      removeBtn.addEventListener("click", () => removeImage(imageId));

      const fileName_div = document.createElement("div");
      fileName_div.className = "gallery-file-name";
      fileName_div.textContent = fileName;
      fileName_div.style.cssText = `
        padding: 5px;
        font-size: 11px;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        background: white;
      `;

      previewItem.appendChild(img);
      previewItem.appendChild(removeBtn);
      previewItem.appendChild(fileName_div);

      previewContainer.appendChild(previewItem);
    }

    // Update mobile preview (grid and carousel views)
    function updateMobilePreview() {

      const $gridView = $(`.mobilelayoutmain #linkpage-id .image-gallery-view[data-preview-for="${widgetId}"] .image-grid-view`);
      const $carouselView = $(`.mobilelayoutmain #linkpage-id .image-gallery-view[data-preview-for="${widgetId}"] .image-carousel-view`);

      // Clear existing images
      $gridView.empty();
      $carouselView.empty();

      // Add images to grid view
      uploadedImages.forEach(img => {
        const $gridImg = $(`<img class="inner-gallery-view" src="${img.src}" alt="${img.name}">`);
        $gridView.append($gridImg);
      });

      // Add images to carousel view
      uploadedImages.forEach(img => {
        const $carouselImg = $(`<div><img src="${img.src}" alt="${img.name}"></div>`);
        $carouselView.append($carouselImg);
      });

      // Initialize or reinitialize slick carousel if more than 1 image
      if (uploadedImages.length > 0) {

        // Destroy existing slick instance if exists
        if ($carouselView.hasClass('slick-initialized')) {
          $carouselView.slick('unslick');
        }

        // Initialize slick carousel
        $carouselView.slick({
          slidesToShow: 1,
          slidesToScroll: 1,
          infinite: true,
          autoplay: true,
          autoplaySpeed: 2000,
          arrows: true,
          dots: false,
          pauseOnHover: true,
          adaptiveHeight: true,
        });
      }
    }

    // Remove image from gallery
    function removeImage(imageId) {

      // Remove from uploadedImages array
      uploadedImages = uploadedImages.filter(img => img.id !== imageId);

      // Remove preview element
      const previewItem = previewContainer.querySelector(`[data-image-id="${imageId}"]`);
      if (previewItem) {
        previewItem.remove();
      }

      // Update mobile preview
      updateMobilePreview();

      // Show placeholder if no images
      if (uploadedImages.length === 0) {
        placeholder.style.display = "block";
        previewContainer.style.display = "none";
        fileInput.value = "";
      }
    }

    // Click → Upload
    fileInput.addEventListener("change", function () {
      if (this.files.length > 0) {
        processFiles(Array.from(this.files));
      }
    });

    // Drag events
    ["dragenter", "dragover"].forEach(eventName =>
      galleryArea.addEventListener(eventName, highlight)
    );

    ["dragleave", "drop"].forEach(eventName =>
      galleryArea.addEventListener(eventName, unhighlight)
    );

    galleryArea.addEventListener("drop", handleDrop);

    // Public method to get all uploaded images
    galleryArea.getUploadedImages = function () {
      return uploadedImages;
    };

    // Public method to clear all images
    galleryArea.clearAllImages = function () {
      uploadedImages.forEach(img => removeImage(img.id));
    };
  }

  // Function 1: Background Image Upload
  function initBackgroundImageUpload(dropArea) {

    if (!dropArea) return;

    const fileInput = dropArea.querySelector(".upload-input");
    const previewBox = dropArea.querySelector(".cover-preview");
    const previewImg = dropArea.querySelector(".preview-img");
    const removeBtn = dropArea.querySelector(".remove-btn");
    const placeholder = dropArea.querySelector(".upload-placeholder");

    // If widget does NOT contain uploader, skip
    if (!fileInput || !previewImg || !previewBox) return;

    // Ensure only images are accepted
    fileInput.setAttribute('accept', 'image/*');

    let uploadedFile = null;

    // Highlight on drag
    function highlight(e) {
      e.preventDefault();
      e.stopPropagation();
      dropArea.classList.add("highlight");
    }

    // Remove highlight
    function unhighlight(e) {
      e.preventDefault();
      e.stopPropagation();
      dropArea.classList.remove("highlight");
    }

    // Handle dropped files
    function handleDrop(e) {
      e.preventDefault();
      let dt = e.dataTransfer;
      let file = dt.files[0];
      processFile(file);
    }

    // Validations + Preview
    function processFile(file) {

      if (!file.type.startsWith("image/")) {
        alert("Only image files are allowed!");
        return;
      }

      if (file.size > 5 * 1024 * 1024) {
        alert("File must be less than 5MB");
        return;
      }

      let reader = new FileReader();

      reader.onload = function (e) {
        const imageUrl = e.target.result;

        // Set preview image
        previewImg.src = imageUrl;
        previewImg.style.height = '120px';
        previewImg.style.width = 'auto';
        previewImg.style.objectFit = 'contain';
        previewImg.style.paddingBottom = '10px';

        // Update CSS variable for background image
        updateBackgroundImageVariable(imageUrl);

        // Store uploaded file
        uploadedFile = {
          file: file,
          type: 'image',
          url: imageUrl
        };

        // Show preview, hide placeholder
        previewBox.style.display = "block";
        placeholder.style.display = "none";
      };

      reader.readAsDataURL(file);
    }

    // Update CSS variable for background image
    function updateBackgroundImageVariable(imageUrl) {
      // Set CSS variable on the root
      document.documentElement.style.setProperty('--preview-background-image', `url(${imageUrl})`);

      // Also set on mobile preview container if exists
      const mobileDevice = document.querySelector('.mobiledevice');
      if (mobileDevice) {
        mobileDevice.style.setProperty('--preview-background-image', `url(${imageUrl})`);
        mobileDevice.classList.add('has-background-image');
      }
    }

    // Clear CSS variable
    function clearBackgroundImageVariable() {
      document.documentElement.style.removeProperty('--preview-background-image');

      const mobileDevice = document.querySelector('.mobiledevice');
      if (mobileDevice) {
        mobileDevice.style.removeProperty('--preview-background-image');
        mobileDevice.classList.remove('has-background-image');
      }
    }

    // Remove preview
    removeBtn?.addEventListener("click", () => {

      previewImg.src = "";
      previewBox.style.display = "none";
      placeholder.style.display = "block";
      fileInput.value = "";

      // Clear CSS variable
      clearBackgroundImageVariable();

      // Clear uploaded file
      uploadedFile = null;
    });

    // Click → Upload
    fileInput.addEventListener("change", function () {
      if (this.files.length > 0) {
        processFile(this.files[0]);
      }
    });

    // Drag events
    ["dragenter", "dragover"].forEach(eventName =>
      dropArea.addEventListener(eventName, highlight)
    );

    ["dragleave", "drop"].forEach(eventName =>
      dropArea.addEventListener(eventName, unhighlight)
    );

    dropArea.addEventListener("drop", handleDrop);

    // Public method to get uploaded file
    dropArea.getUploadedFile = function () {
      return uploadedFile;
    };
  }

  // Function 2: Background Video Upload
  function initBackgroundVideoUpload(dropArea) {

    if (!dropArea) return;

    const fileInput = dropArea.querySelector(".upload-input");
    const previewBox = dropArea.querySelector(".cover-preview");
    const previewImg = dropArea.querySelector(".preview-img");
    const removeBtn = dropArea.querySelector(".remove-btn");
    const placeholder = dropArea.querySelector(".upload-placeholder");

    // If widget does NOT contain uploader, skip
    if (!fileInput || !previewImg || !previewBox) return;

    // Ensure only videos are accepted
    fileInput.setAttribute('accept', 'video/*');

    let uploadedFile = null;

    // Highlight on drag
    function highlight(e) {
      e.preventDefault();
      e.stopPropagation();
      dropArea.classList.add("highlight");
    }

    // Remove highlight
    function unhighlight(e) {
      e.preventDefault();
      e.stopPropagation();
      dropArea.classList.remove("highlight");
    }

    // Handle dropped files
    function handleDrop(e) {
      e.preventDefault();
      let dt = e.dataTransfer;
      let file = dt.files[0];
      processFile(file);
    }

    // Validations + Preview
    function processFile(file) {

      if (!file.type.startsWith("video/")) {
        alert("Only video files are allowed!");
        return;
      }

      // 50MB limit for videos
      if (file.size > 50 * 1024 * 1024) {
        alert("File must be less than 50MB");
        return;
      }

      handleVideoFile(file);
    }

    // Handle video file and generate thumbnail
    function handleVideoFile(file) {
      const videoUrl = URL.createObjectURL(file);
      const video = document.createElement('video');

      video.preload = 'metadata';
      video.muted = true;

      video.onloadeddata = function () {
        // Seek to 1 second to get a good thumbnail frame
        video.currentTime = Math.min(1, video.duration);
      };

      video.onseeked = function () {
        // Create canvas to capture video thumbnail
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        // Get thumbnail as data URL
        const thumbnailUrl = canvas.toDataURL('image/jpeg', 0.8);

        // Update CSS variable with video thumbnail
        updateBackgroundVideoVariable(thumbnailUrl);

        // Show video icon in preview
        previewImg.src = getVideoIconSVG();
        previewImg.style.height = '80px';
        previewImg.style.width = 'auto';
        previewImg.style.objectFit = 'contain';
        previewImg.style.paddingBottom = '10px';
        previewImg.style.filter = 'none';

        // Store uploaded file
        uploadedFile = {
          file: file,
          type: 'video',
          url: videoUrl,
          thumbnail: thumbnailUrl
        };

        // Show preview, hide placeholder
        previewBox.style.display = "block";
        placeholder.style.display = "none";

        // Clean up video element
        video.remove();
      };

      video.onerror = function () {
        alert("Error loading video file");
        URL.revokeObjectURL(videoUrl);
        video.remove();
      };

      video.src = videoUrl;
    }

    // Update CSS variable for background video
    function updateBackgroundVideoVariable(thumbnailUrl) {
      // Set CSS variable on the root
      document.documentElement.style.setProperty('--preview-background-video', `url(${thumbnailUrl})`);

      // Also set on mobile preview container if exists
      const mobileDevice = document.querySelector('.mobiledevice');
      if (mobileDevice) {
        mobileDevice.style.setProperty('--preview-background-video', `url(${thumbnailUrl})`);
        mobileDevice.classList.add('has-background-video');
      }
    }

    // Clear CSS variable
    function clearBackgroundVideoVariable() {
      document.documentElement.style.removeProperty('--preview-background-video');

      const mobileDevice = document.querySelector('.mobiledevice');
      if (mobileDevice) {
        mobileDevice.style.removeProperty('--preview-background-video');
        mobileDevice.classList.remove('has-background-video');
      }
    }

    // Get video icon SVG as data URL
    function getVideoIconSVG() {
      const svg = `
        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polygon points="23 7 16 12 23 17 23 7"></polygon>
          <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
        </svg>
      `;
      return 'data:image/svg+xml;base64,' + btoa(svg);
    }

    // Remove preview
    removeBtn?.addEventListener("click", () => {

      previewImg.src = "";
      previewBox.style.display = "none";
      placeholder.style.display = "block";
      fileInput.value = "";

      // Clear CSS variable
      clearBackgroundVideoVariable();

      // Revoke object URL if exists
      if (uploadedFile && uploadedFile.url) {
        URL.revokeObjectURL(uploadedFile.url);
      }

      // Clear uploaded file
      uploadedFile = null;
    });

    // Click → Upload
    fileInput.addEventListener("change", function () {
      if (this.files.length > 0) {
        processFile(this.files[0]);
      }
    });

    // Drag events
    ["dragenter", "dragover"].forEach(eventName =>
      dropArea.addEventListener(eventName, highlight)
    );

    ["dragleave", "drop"].forEach(eventName =>
      dropArea.addEventListener(eventName, unhighlight)
    );

    dropArea.addEventListener("drop", handleDrop);

    // Public method to get uploaded file
    dropArea.getUploadedFile = function () {
      return uploadedFile;
    };

    // Public method to get thumbnail URL
    dropArea.getThumbnailUrl = function () {
      return uploadedFile ? uploadedFile.thumbnail : null;
    };
  }
  document.querySelectorAll(".appendwidgetboxcontainer .drop-area").forEach(dropArea => {
    initDropArea(dropArea);
  });

  document.querySelectorAll(".drop-area-background-image").forEach(dropArea => {
    initBackgroundImageUpload(dropArea);
  });
  document.querySelectorAll(".drop-area-background-video").forEach(dropArea => {
    initBackgroundVideoUpload(dropArea);
  });

  initDropAreaLayout();
  initDropAreaProfile();
  function create_preview(selector_id) {
    var $element = $(".appendwidgetboxcontainer #" + selector_id);
    if (!$element.length) {
      console.warn("Element not found:", selector_id);
      return;
    }

    var fieldType = $element.attr("data-widget-type");

    console.log("fieldType:", fieldType);
    console.log("selector_id:", selector_id);

    const $previewContainer = $(
      ".mobilelayoutmain #linkpage-id .linkpage-body"
    );
    var previewEl = "";

    switch (fieldType) {
      case "button":
        previewEl = $(`
                    <div class='btn rounded-filled' style="min-width: 100%;margin: 8px 4px;"
                                                            >
                                                            <div class="icon-preview">
                                                                <img src="${LinkPages.default_image}" data-default-src="${LinkPages.default_image}" alt="">
                                                            </div>
                                                            <div class="thumbnail-preview">
                                                                <img src="${LinkPages.default_image}" data-default-src="${LinkPages.default_image}" alt="">
                                                            </div>
                                                            <button
                                                                style='  position: relative; background: transparent;  width: 100%;; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border: none; color: #ffffff; font-size: 14px; padding: 0;'>Button
                                                                title</button>
                                                        </div>
                `);
        break;

      case "seperator":
        previewEl = $(`
                    <div class="separator">
                        <div class="mid-line test-line "></div>
                    </div>
                `);
        break;

      case "link":
        previewEl = $(`
                    <div class='btn rounded-filled' style="min-width: 100%;margin: 8px 4px;"
                                                            >
                                                            <div class="icon-preview">
                                                                <img src="${LinkPages.default_image}" data-default-src="${LinkPages.default_image}" alt="">
                                                            </div>
                                                            <div class="thumbnail-preview">
                                                                <img src="${LinkPages.default_image}" data-default-src="${LinkPages.default_image}" alt="">
                                                            </div>
                                                            <button
                                                                style='  position: relative; background: transparent;  width: 100%;; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border: none; color: #ffffff; font-size: 14px; padding: 0;'>Button
                                                                title</button>
                                                        </div>
                `);
        break;

      case "landing":
        previewEl = $(`
                    <div class="btn rounded-filled" style="min-width: 100%; margin: 8px 4px; height: 45px;">
                        <button style="position: relative; background: transparent; max-width: 80%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border: none; color: #ffffff; font-size: 14px; padding: 0;">Landing Page</button>
                    </div>
                `);
        break;

      case "form":
        previewEl = $(`
                    <div class="btn rounded-filled" style="min-width: 100%; margin: 8px 4px; height: 45px;">
                        <button style="position: relative; background: transparent; max-width: 80%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border: none; color: #ffffff; font-size: 14px; padding: 0;">Form</button>
                    </div>
                `);
        break;

      case "social":
        previewEl = $(`
                    <div class="social-liks">
                        <div class="title-box text-center" style="font-color:#000000;">Connect with us!</div>
                        <div class="social-icon-box">
                            
                        </div>
                    </div>
                `);
        break;

      case "text-box":
        previewEl = $(`
                    <div class="text-box-preview" style="margin: 8px 0; padding: 8px;">
                        <p style="color: #000;">Text content here</p>
                    </div>
                `);
        break;

      case "embaded":
        previewEl = $(`
                    <div class="embaded-video">
                        <iframe width="220" height="150" src="" frameborder="0" allowfullscreen></iframe>
                    </div>
                `);
        break;

      case "img-gallery":
        previewEl = $(`
                    <div class="image-gallery-view">
                        <div class="image-grid-view">
                        </div>
                        <div class="image-carousel-view" style="display:none">
                        </div>
                    </div>
                `);
        break;

      case "email":
        previewEl = $(`
                    <div class="btn rounded-filled" style="min-width: 100%; margin: 8px 4px;">
                        <div class="icon-preview">
                            <img src="${LinkPages.default_image}" data-default-src="${LinkPages.default_image}" alt="">
                        </div>
                        <div class="thumbnail-preview">
                            <img src="${LinkPages.default_image}" data-default-src="${LinkPages.default_image}" alt="">
                        </div>
                        <button style="position: relative; background: transparent; width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border: none; color: #ffffff; font-size: 14px; padding: 0;">Email</button>
                    </div>
                `);
        break;

      case "call":
        previewEl = $(`
                    <div class="btn rounded-filled" style="min-width: 100%; margin: 8px 4px;">
                      <div class="icon-preview">
                            <img src="${LinkPages.default_image}" data-default-src="${LinkPages.default_image}" alt="">
                        </div>
                        <div class="thumbnail-preview">
                            <img src="${LinkPages.default_image}" data-default-src="${LinkPages.default_image}" alt="">
                        </div>
                        <button style="position: relative; background: transparent; width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border: none; color: #ffffff; font-size: 14px; padding: 0;">Call</button>
                    </div>
                `);
        break;

      case "sms":
        previewEl = $(`
                    <div class="btn rounded-filled" style="min-width: 100%; margin: 8px 4px;">
                        <div class="icon-preview">
                            <img src="${LinkPages.default_image}" data-default-src="${LinkPages.default_image}" alt="">
                        </div>
                        <div class="thumbnail-preview">
                            <img src="${LinkPages.default_image}" data-default-src="${LinkPages.default_image}" alt="">
                        </div>
                        <button style="position: relative; background: transparent; width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border: none; color: #ffffff; font-size: 14px; padding: 0;">SMS</button>
                    </div>
                `);
        break;

      case "upload-pdf":
        previewEl = $(`
                    <div class="btn rounded-filled" style="min-width: 100%; margin: 8px 4px; height: 45px;">
                        <button style="position: relative; background: transparent; max-width: 80%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border: none; color: #ffffff; font-size: 14px; padding: 0;">PDF Document</button>
                    </div>
                `);
        break;

      case "location":
        previewEl = $(`
                    <div class="btn rounded-filled" style="min-width: 100%;max-width: 100%; margin: 8px 4px; padding:0px;">
                            <svg class="location-pin" width="15" viewBox="0 0 12 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M5.42142 15.1974C1.16465 9.0263 0.374512 8.39296 0.374512 6.125C0.374512 3.01839 2.8929 0.5 5.99951 0.5C9.10612 0.5 11.6245 3.01839 11.6245 6.125C11.6245 8.39296 10.8344 9.0263 6.5776 15.1974C6.29825 15.6009 5.70074 15.6009 5.42142 15.1974ZM5.99951 8.46875C7.29393 8.46875 8.34326 7.41942 8.34326 6.125C8.34326 4.83057 7.29393 3.78125 5.99951 3.78125C4.70509 3.78125 3.65576 4.83057 3.65576 6.125C3.65576 7.41942 4.70509 8.46875 5.99951 8.46875Z" fill="#ffffff"></path>
                            </svg>
                            <button style="position: relative; background: transparent; max-width: 80%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border: none; color: #ffffff; font-size: 14px;padding-top: 15px;padding-bottom: 15px;">Location</button>
                        <div class="map-preview" style="display:none;"></div>
                    </div>
                `);
        break;

      case "business-hours":
        previewEl = $(`
                    <div class="hours-card rounded-filled">
                        <div class="hours-toggle">
                            <div class="hours-left">
                                <span class="icon">
                                    <i class="fa-solid fa-clock"></i>
                                </span>

                                <div class="status-text">
                                    <strong>OPEN NOW</strong>
                                    <small>Closes 6:00 PM · 9:00 AM to 6:00 PM (IST)</small>
                                </div>
                            </div>

                            <div class="arrow">
                                <svg id="business-arrow-contatiner--4" class="arrow-svg" xmlns="http://www.w3.org/2000/svg" width="14" height="16" viewBox="0 0 14 16" fill="none">
                                    <path d="M7.45347 3.99494L12.6456 9.60727C12.896 9.87795 12.896 10.3168 12.6456 10.5874L12.04 11.242C11.79 11.5122 11.3849 11.5128 11.1343 11.2432L7.00007 6.7953L2.86585 11.2432C2.61527 11.5128 2.21012 11.5122 1.96014 11.242L1.35456 10.5874C1.10414 10.3168 1.10414 9.87792 1.35456 9.60728L6.54669 3.99497C6.79708 3.72429 7.20306 3.72429 7.45347 3.99494Z" fill="#ffffff"></path>
                                </svg>
                            </div>
                        </div>

                        <div class="hours-content">
                            <ul>
                                <li data-day='monday'><span>Monday</span> <span class='status'>9:00 AM – 6:00 PM</span></li>
                                <li data-day='tuesday'><span>Tuesday</span> <span class='status'>9:00 AM – 6:00 PM</span></li>
                                <li data-day='wednesday'><span>Wednesday</span> <span class='status'>9:00 AM – 6:00 PM</span></li>
                                <li data-day='thursday'><span>Thursday</span> <span class='status'>9:00 AM – 6:00 PM</span></li>
                                <li data-day='friday'><span>Friday</span> <span class='status'>9:00 AM – 6:00 PM</span></li>
                                <li data-day='saturday'><span>Saturday</span> <span class='status'>9:00 AM – 6:00 PM</span></li>
                                <li data-day='sunday'><span>Sunday</span> <span class='status'>9:00 AM – 6:00 PM</span></li>
                            </ul>
                        </div>
                    </div>
                     <style>
                                                        .mobiledevice {
                                                            overflow: auto;
                                                        }

                                                        .mobiledevice .rounded-filled:hover,
                                                        .mobiledevice .rounded-filled:focus {
                                                            background: #000 !important;
                                                        }

                                                        .separator {
                                                            margin: 8px 0px;
                                                            display: block;
                                                        }

                                                        .mid-line {
                                                            background: #000;
                                                            width: 100%;
                                                            height: 1px;
                                                        }

                                                        /* btn with icon css start */
                                                        .btn-with-icon {
                                                            position: relative;
                                                            overflow: hidden;
                                                        }

                                                        .btn-with-icon>div {
                                                            position: absolute;
                                                            top: 0;
                                                            left: 0;
                                                            bottom: 0;
                                                        }

                                                        .btn-with-icon>div img.rounded-image-icon {
                                                            width: 45px;
                                                            height: auto;
                                                        }

                                                        /* btn with icon css end */

                                                        /* btn with img thumbnail css start */

                                                        .btn-with-thumbnail {
                                                            padding: 0;
                                                            overflow: hidden;
                                                            position: relative;
                                                        }

                                                        .btn-with-thumbnail img.rounded-image-thumbnail {
                                                            width: 100%;
                                                            height: 128px;
                                                            display: flex;
                                                            object-fit: cover;
                                                        }

                                                        /* btn with img thumbnail css end */

                                                        /* social links css start */
                                                        .social-liks {
                                                            margin: 8px;
                                                        }

                                                        .social-liks .title-box {
                                                            color: #000;
                                                            margin-bottom: 8px;
                                                        }

                                                        .social-liks .social-icon-box {
                                                            display: flex;
                                                            flex-wrap: wrap;
                                                            gap: 8px;
                                                            justify-content: center;
                                                        }

                                                        .social-liks .social-icon-box a.btn-icon {
                                                            width: 37px;
                                                            margin: 0;
                                                            border-radius: 50%;
                                                        }

                                                        .social-liks .social-icon-box a.btn-icon img.social-icon-img {
                                                            width: 100%;
                                                            height: 100%;
                                                            border-radius: 50%;
                                                        }

                                                        /* social links css end */

                                                        /* social button css start */
                                                        .social-button-box .social-btn-link {
                                                            width: 100%;
                                                            position: relative;
                                                        }

                                                        a.btn.rounded-filled.social-btn-link {
                                                            display: flex;
                                                            align-items: center;
                                                            color: #fff;
                                                            text-decoration: none;
                                                            text-align: center;
                                                        }

                                                        .social-button-box .social-btn-link span.icon-box {
                                                            float: left;
                                                        }

                                                        .social-button-box .social-btn-link span.icon-box img.social-icon-img {
                                                            width: 37px;
                                                            height: 37px;
                                                            border-radius: 50%;
                                                        }

                                                        .social-button-box .social-btn-link span.text-box {
                                                            margin-left: 15%;
                                                        }


                                                        /* social button css end */

                                                        /* embaded video css start */
                                                        .embaded-video {
                                                            display: flex;
                                                            margin: 8px 0px;
                                                        }

                                                        .embaded-video iframe {
                                                            width: 100%;
                                                            height: 150px;
                                                        }

                                                        /* embaded video css end */

                                                        /* image gridview css start */
                                                        .image-gallery-view {
                                                            margin: 8px 0px;
                                                            display: flex;
                                                            flex-wrap: wrap;
                                                            gap: 8px;
                                                        }

                                                        .image-gallery-view img.inner-gallery-view {
                                                            width: calc(50% - 4px);
                                                            object-fit: cover;
                                                            border-radius: 2px;
                                                        }

                                                        /* image gridview css end */

                                                        /* image slider css start */

                                                        .image-slider-view img {
                                                            height: 160px;
                                                            width: 100%;
                                                            object-fit: cover;
                                                        }

                                                        .image-slider-view {
                                                            margin: 8px 0px;
                                                        }

                                                        .image-single-img {
                                                            margin: 8px 0px;
                                                        }

                                                        .image-slider-view button.slick-arrow {
                                                            padding: 0;
                                                            margin: 0;
                                                            font-size: 0;
                                                            width: 20px;
                                                            height: 20px;
                                                            border: none;
                                                            background: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23fff' viewBox='0 0 8 8'%3E%3Cpath d='M5.25 0l-4 4 4 4 1.5-1.5-2.5-2.5 2.5-2.5-1.5-1.5z'/%3E%3C/svg%3E");
                                                            background-size: contain !important;
                                                            z-index: 1;
                                                        }

                                                        .image-slider-view button.slick-arrow.slick-prev {
                                                            left: 0px;
                                                        }

                                                        .image-slider-view button.slick-arrow.slick-next {
                                                            right: 0px;
                                                            transform: scaleX(-1) translateY(-50%);
                                                        }

                                                        .image-slider-view button.slick-arrow::before {
                                                            display: none;
                                                        }

                                                        /* location btn css start */
                                                        .location-box {
                                                            color: #fff;
                                                            margin: 8px 0px;
                                                        }

                                                        a.location-btn {
                                                            text-decoration: none;
                                                            color: #fff;
                                                            display: flex;
                                                            /* justify-content: space-evenly; */
                                                            flex-wrap: wrap;
                                                            width: 100%;
                                                        }


                                                        span.loaction-img-box {
                                                            margin: 0px -16px -8px;
                                                        }

                                                        a.location-btn {
                                                            overflow: hidden;
                                                        }

                                                        a.location-btn span.location-text {
                                                            max-width: 80%;
                                                            max-height: 3lh;
                                                            text-overflow: ellipsis;
                                                            overflow: hidden;
                                                            text-align: center;
                                                            display: -webkit-box;
                                                            -webkit-line-clamp: 3;
                                                            -webkit-box-orient: vertical;
                                                            display: block;
                                                            margin: 10px auto;
                                                            font-size: 11px;
                                                        }

                                                        /* location btn css end */

                                                        /* hours toggle css start */
                                                        .hours-card {
                                                            width: 100%;
                                                            // background: var(--btn-bg-color);
                                                            color: var(--btn-text-color);
                                                            border-radius: 20px;
                                                            padding: 6px 10px;
                                                        }

                                                        .hours-toggle {
                                                            display: flex;
                                                            align-items: center;
                                                            justify-content: space-between;
                                                            cursor: pointer;
                                                        }

                                                        .hours-left {
                                                            display: flex;
                                                            align-items: center;
                                                            gap: 10px;
                                                        }

                                                        .hours-toggle .icon {
                                                            font-size: 20px;
                                                        }

                                                        .status-text strong {
                                                            font-size: 14px;
                                                            display: block;
                                                            text-align: center;
                                                        }

                                                        .status-text small {
                                                            font-size: 10px;
                                                            opacity: 0.7;
                                                        }

                                                        .arrow {
                                                            font-size: 18px;
                                                            transition: transform 0.3s;
                                                            display: flex;
                                                            align-items: center;
                                                            justify-content: center;
                                                        }

                                                        .hours-content {
                                                            display: none;
                                                            margin-top: 15px;
                                                        }

                                                        .hours-content ul {
                                                            padding: 0;
                                                            margin: 0;
                                                            list-style: none;
                                                        }

                                                        .hours-content li {
                                                            display: flex;
                                                            justify-content: space-between;
                                                            padding: 6px 0;
                                                            font-size: 11px;
                                                        }

                                                        .status-text {
                                                            text-align: center;
                                                        }

                                                        /* hours toggle css end */
                                                    </style>
                                                    <script>
                                                        jQuery(function($) {
                                                            $('.slick-slider').slick({
                                                                slidesToShow: 1,
                                                                slidesToScroll: 1,
                                                                infinite: true,
                                                                autoplay: true,
                                                                autoplaySpeed: 2000,
                                                                arrows: true,
                                                                dots: false,
                                                                pauseOnHover: true,
                                                                adaptiveHeight: true,
                                                            });
                                                        });
                                                        document.querySelector(".hours-toggle").addEventListener("click", function() {
                                                            const content = document.querySelector(".hours-content");
                                                            const arrow = document.querySelector(".arrow");

                                                            if (content.style.display === "block") {
                                                                content.style.display = "none";
                                                                arrow.style.transform = "scaleY(1)";
                                                            } else {
                                                                content.style.display = "block";
                                                                arrow.style.transform = "scaleY(-1)";
                                                            }
                                                        });
                                                    </script>
                `);
        break;

      default:
        console.warn("Unknown field type:", fieldType);
        return;
    }

    if (previewEl) {
      // Link this preview element to the widget instance so we can update/remove it later
      previewEl.attr("data-preview-for", selector_id);
      $previewContainer.append(previewEl);
      if (fieldType == 'business-hours') {
        $('.openclose input[type="checkbox"]').each(function () {
          if (this.hasAttribute("checked")) {
            this.checked = true;
          }
        });
      }
      // If this is a separator widget, immediately sync its current controls to the preview
      if (fieldType === "seperator") {
        // trigger input on color/thickness controls inside the widget so the delegated handler applies styles
        $element
          .find(
            '.lp-w-separator-color, .lp-w-separator-hexcolor, input[name^="lp-w-separator-t-number"], input[id^="lp-w-separator-t-range"]'
          )
          .trigger("input");
      }
    }
  }
  function enableAutocompleteForInput(input) {
    const $input = $(input);
    const $widget = $input.closest('.widgetbox');
    const $mapContainer = $widget.find('.map-container');
    const $addressInput = $widget.find('.location-address');
    const $latInput = $widget.find('.location-lat');
    const $lngInput = $widget.find('.location-lng');

    // Default location (India)
    const defaultLocation = { lat: 20.5937, lng: 78.9629 };

    // Check if map is already initialized
    if ($mapContainer.data('map-initialized')) {
      return;
    }

    // Initialize map
    const map = new google.maps.Map($mapContainer[0], {
      center: defaultLocation,
      zoom: 5,
    });

    $mapContainer.data('map-initialized', true);
    $mapContainer.data('map-instance', map);

    const marker = new google.maps.Marker({
      map: map,
      position: defaultLocation,
      draggable: true
    });

    $mapContainer.data('marker-instance', marker);

    // Autocomplete
    const autocomplete = new google.maps.places.Autocomplete(input);
    autocomplete.bindTo('bounds', map);
    updateMapPreview($widget.attr('id'), defaultLocation.lat, defaultLocation.lng);
    autocomplete.addListener("place_changed", function () {
      const place = autocomplete.getPlace();
      if (!place.geometry || !place.geometry.location) {
        return;
      }

      // Update map
      if (place.geometry.viewport) {
        map.fitBounds(place.geometry.viewport);
      } else {
        map.setCenter(place.geometry.location);
        map.setZoom(17);
      }

      marker.setPosition(place.geometry.location);

      // Update inputs
      $addressInput.val(place.formatted_address).trigger('change');
      $latInput.val(place.geometry.location.lat());
      $lngInput.val(place.geometry.location.lng());

      updateMapPreview($widget.attr('id'), place.geometry.location.lat(), place.geometry.location.lng());
    });

    // Map Click
    map.addListener('click', function (e) {
      marker.setPosition(e.latLng);
      geocodePosition(e.latLng, $input, $addressInput, $latInput, $lngInput);
    });

    // Marker Drag
    marker.addListener('dragend', function () {
      geocodePosition(marker.getPosition(), $input, $addressInput, $latInput, $lngInput);
    });
  }

  function initTimezoneSelection(place = null) {
    let detectedZone = 'America/New_York'; // Default
    try {
      detectedZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    } catch (e) {
      console.warn('Timezone detection failed, using default:', e);
    }

    // Update all timezone selects, including the template
    if (place) {
      $(place).find('.timezone-selection').each(function () {
        const $select = $(this);
        // Check if the detected zone exists in the options
        if ($select.find(`option[value="${detectedZone}"]`).length > 0) {
          $select.val(detectedZone).trigger('change');
        } else {
          $select.val('America/New_York');
        }
      });
    } else {
      $('.timezone-selection').each(function () {
        const $select = $(this);
        // Check if the detected zone exists in the options
        if ($select.find(`option[value="${detectedZone}"]`).length > 0) {
          $select.val(detectedZone).trigger('change');
        } else {
          $select.val('America/New_York');
        }
      });
    }

  }

  // Run timezone detection on load
  initTimezoneSelection();

  function geocodePosition(pos, $searchInput, $addressInput, $latInput, $lngInput) {
    const geocoder = new google.maps.Geocoder();
    geocoder.geocode({
      latLng: pos
    }, function (responses) {
      if (responses && responses.length > 0) {
        const address = responses[0].formatted_address;
        $searchInput.val(address);
        $addressInput.val(address).trigger('change');
        $latInput.val(pos.lat());
        $lngInput.val(pos.lng());

        const widgetId = $searchInput.closest('.widgetbox').attr('id');
        updateMapPreview(widgetId, pos.lat(), pos.lng());
      }
    });
  }

  function updateMapPreview(widgetId, lat, lng) {
    const $previewContainer = $(`.mobilelayoutmain #linkpage-id .linkpage-body .btn.rounded-filled[data-preview-for="${widgetId}"]`);
    const $mapPreview = $previewContainer.find('.map-preview');

    if ($mapPreview.length) {
      // Ensure the container has height
      $previewContainer.css('height', 'auto');
      $mapPreview.css({
        'width': '100%',
        'height': '150px',
        'margin-top': '10px',
        'border-bottom-left-radius': '20px',
        'border-bottom-right-radius': '20px'
      });

      // Check if map is already initialized in preview
      if ($mapPreview.data('map-initialized')) {
        const map = $mapPreview.data('map-instance');
        const center = new google.maps.LatLng(lat, lng);
        map.setCenter(center);
        const marker = $mapPreview.data('marker-instance');
        marker.setPosition(center);
      } else {
        // Initialize map in preview
        const center = new google.maps.LatLng(lat, lng);
        const map = new google.maps.Map($mapPreview[0], {
          center: center,
          zoom: 15,
          disableDefaultUI: true,
          draggable: false,
          zoomControl: false,
          scrollwheel: false,
          disableDoubleClickZoom: true
        });

        const marker = new google.maps.Marker({
          map: map,
          position: center
        });

        $mapPreview.data('map-initialized', true);
        $mapPreview.data('map-instance', map);
        $mapPreview.data('marker-instance', marker);
      }
    }
  }

  window.updateSocialLinkTitle = function (input) {
    const $input = $(input);
    const newTitle = $input.val().trim();
    const $row = $input.closest(".socialicon-subdiv");
    const $img = $row.find("img.socialimg");
    const alt = $img.attr("alt");
    const widgetId = $input.closest(".widgetbox").attr("id");
    const $socialPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .social-liks[data-preview-for="${widgetId}"] .social-icon-box`
    );
    // Update title in mobile preview
    $socialPreview.find(`a[data-social="${alt}"]`).each(function () {
      if ($(this).find("img.socialimg").length) {
        if ($(this).hasClass("btn")) {
          // It's a button style
          $(this).html(
            `<img src="${$img.attr("src")}" alt="${alt}" class="socialimg" style="width:20px;height:20px;margin-right:8px;"> ${newTitle}`
          );
        }
      }
    });
  };
  window.updateLocationAddress = function (input) {
    const $input = $(input);
    const newAddress = $input.val().trim();
    const widgetId = $input.closest(".widgetbox").attr("id");
    const $locationPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .btn.rounded-filled[data-preview-for="${widgetId}"] button`
    );
    // Update location in mobile preview
    $locationPreview.text(newAddress);
  };
  window.enableMapPreview = function (checkbox) {
    console.log('enableMapPreview');
    const $checkbox = $(checkbox);
    const isChecked = $checkbox.is(":checked");
    const widgetId = $checkbox.closest(".widgetbox").attr("id");
    const $locationPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .btn.rounded-filled[data-preview-for="${widgetId}"] .map-preview`
    );
    if (isChecked) {
      $locationPreview.show();
    } else {
      $locationPreview.hide();
    }
  };
  window.updateTextSectionInput = function (input) {
    const $input = $(input);
    const newText = $input.val().trim();
    const widgetId = $input.closest(".widgetbox").attr("id");
    const $textPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .text-box-preview[data-preview-for="${widgetId}"] p`
    );
    // Update text content in mobile preview
    $textPreview.text(newText);
  };
  window.updateTextSectionFont = function (select) {
    const $select = $(select);
    const newFont = $select.val();
    const widgetId = $select.closest(".widgetbox").attr("id");
    const $textPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .text-box-preview[data-preview-for="${widgetId}"] p`
    );
    // Update font style in mobile preview
    switch (newFont) {
      case "normal":
        $textPreview.css({
          "font-size": "14px",
          "font-weight": "normal",
          "line-height": "1.5",
          "margin": "0",
        });
        break;
      case "h1":
        $textPreview.css({
          "font-size": "32px",
          "font-weight": "bold",
          "line-height": "1.2",
          "margin": "0",
        });
        break;
      case "h2":
        $textPreview.css({
          "font-size": "24px",
          "font-weight": "bold",
          "line-height": "1.3",
          "margin": "0",
        });
        break;
      case "h3":
        $textPreview.css({
          "font-size": "18px",
          "font-weight": "bold",
          "line-height": "1.4",
          "margin": "0",
        });
        break;
      default:
        // Default to normal
        $textPreview.css({
          "font-size": "14px",
          "font-weight": "normal",
          "line-height": "1.5",
          "margin": "0",
        });
        break;
    }
  };
  window.updateTextSectionColor = function (input) {
    const $input = $(input);
    const newColor = $input.val().trim();
    const widgetId = $input.closest(".widgetbox").attr("id");
    const $textPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .text-box-preview[data-preview-for="${widgetId}"] p`
    );
    // Update text color in mobile preview
    $textPreview.css("color", newColor);
  };
  window.updateTextSectionAlign = function (button, alignment) {
    const $button = $(button);
    const widgetId = $button.closest(".widgetbox").attr("id");
    const $textPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .text-box-preview[data-preview-for="${widgetId}"] p`
    );
    // Update text alignment in mobile preview
    $textPreview.css("text-align", alignment);
    $button.addClass("active").siblings().removeClass("active");
  };
  window.updateTextSectionStyle = function (button, style) {
    const $button = $(button);
    const widgetId = $button.closest(".widgetbox").attr("id");
    const $textPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .text-box-preview[data-preview-for="${widgetId}"] p`
    );
    // Toggle text style in mobile preview
    switch (style) {
      case "bold":
        const isBold = $textPreview.css("font-weight") === "700" || $textPreview.css("font-weight") === "bold";
        $textPreview.css("font-weight", isBold ? "normal" : "bold");
        break;
      case "intalic":
        const isItalic = $textPreview.css("font-style") === "italic";
        $textPreview.css("font-style", isItalic ? "normal" : "italic");
        break;
      case "underline":
        const isUnderline = $textPreview.css("text-decoration-line").includes("underline");
        $textPreview.css("text-decoration", isUnderline ? "none" : "underline");
        break;
      case "strikethrough":
        const isStrikethrough = $textPreview.css("text-decoration-line").includes("line-through");
        $textPreview.css("text-decoration", isStrikethrough ? "none" : "line-through");
        break;
      default:
        break;
    }
    $button.toggleClass("active");
  };

  window.updateSocialLinkHeadline = function (input) {
    const $input = $(input);
    const newHeadline = $input.val().trim();
    const widgetId = $input.closest(".widgetbox").attr("id");
    const $socialPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .social-liks[data-preview-for="${widgetId}"] .title-box`
    );
    // Update headline in mobile preview
    $socialPreview.text(newHeadline);
  };
  window.updateSocialLinkHeadlineColor = function (input) {
    const $input = $(input);
    const newColor = $input.val().trim();
    const widgetId = $input.closest(".widgetbox").attr("id");
    const $socialPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .social-liks[data-preview-for="${widgetId}"] .title-box`
    );
    // Update headline color in mobile preview
    $socialPreview.css("color", newColor);
  };
  window.updateEmbadedVideoURL = function (input) {
    const $input = $(input);
    const newURL = $input.val().trim();
    const widgetId = $input.closest(".widgetbox").attr("id");
    const $embadedPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .embaded-video[data-preview-for="${widgetId}"] iframe`
    );

    // Convert YouTube URL to embed format
    let embedURL = newURL;

    if (newURL.includes('youtube.com/watch?v=')) {
      // Extract video ID from standard YouTube URL
      const videoId = newURL.split('v=')[1]?.split('&')[0];
      if (videoId) {
        embedURL = `https://www.youtube.com/embed/${videoId}`;
      }
    } else if (newURL.includes('youtu.be/')) {
      // Extract video ID from shortened YouTube URL
      const videoId = newURL.split('youtu.be/')[1]?.split('?')[0];
      if (videoId) {
        embedURL = `https://www.youtube.com/embed/${videoId}`;
      }
    }

    // Get current autoplay and mute settings from checkboxes
    const $widgetBox = $input.closest(".widgetbox");
    const isAutoplay = $widgetBox.find('input[type="checkbox"][onchange*="updateVideoAutoPlay"]').is(":checked");
    const isMuted = $widgetBox.find('input[type="checkbox"][onchange*="updateVideoMute"]').is(":checked");

    // Apply autoplay and mute parameters to embed URL
    const url = new URL(embedURL);
    if (isAutoplay) {
      url.searchParams.set("autoplay", "1");
    }
    if (isMuted) {
      url.searchParams.set("mute", "1");
    }

    // Update embedded video URL in mobile preview
    $embadedPreview.attr("src", url.toString());
  };

  window.updateVideoMute = function (checkbox) {
    const $checkbox = $(checkbox);
    const isMuted = $checkbox.is(":checked");
    const widgetId = $checkbox.closest(".widgetbox").attr("id");
    const $embadedPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .embaded-video[data-preview-for="${widgetId}"] iframe`
    );
    // Update mute setting in embedded video URL in mobile preview
    let src = $embadedPreview.attr("src");
    if (!src) return;
    const url = new URL(src);
    if (isMuted) {
      url.searchParams.set("mute", "1");
    } else {
      url.searchParams.delete("mute");
    }
    $embadedPreview.attr("src", url.toString());
  };

  window.updateVideoAutoPlay = function (checkbox) {
    const $checkbox = $(checkbox);
    const isAutoplay = $checkbox.is(":checked");
    const widgetId = $checkbox.closest(".widgetbox").attr("id");
    const $widgetBox = $checkbox.closest(".widgetbox");
    const $muteCheckbox = $widgetBox.find('input[type="checkbox"][onchange*="updateVideoMute"]');
    const $embadedPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .embaded-video[data-preview-for="${widgetId}"] iframe`
    );

    // If autoplay is enabled, automatically enable mute
    if (isAutoplay && !$muteCheckbox.is(":checked")) {
      $muteCheckbox.prop("checked", true);
    }

    // Update autoplay setting in embedded video URL in mobile preview
    let src = $embadedPreview.attr("src");
    if (!src) return;
    const url = new URL(src);
    if (isAutoplay) {
      url.searchParams.set("autoplay", "1");
      url.searchParams.set("mute", "1"); // Always mute when autoplay is enabled
    } else {
      url.searchParams.delete("autoplay");
    }
    $embadedPreview.attr("src", url.toString());
  };

  window.updateEmailButtonTitle = function (input) {
    const $input = $(input);
    const newTitle = $input.val().trim();
    const widgetId = $input.closest(".widgetbox").attr("id");
    const $emailPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .btn[data-preview-for="${widgetId}"] button`
    );
    // Update button title in mobile preview
    $emailPreview.text(newTitle);
  };
  window.updateCallButtonTitle = function (input) {
    const $input = $(input);
    const newTitle = $input.val().trim();
    const widgetId = $input.closest(".widgetbox").attr("id");
    const $callPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .btn[data-preview-for="${widgetId}"] button`
    );
    // Update button title in mobile preview
    $callPreview.text(newTitle);
  };
  window.updatePDFWidgetButtonText = function (input) {
    const $input = $(input);
    const newTitle = $input.val().trim();
    const widgetId = $input.closest(".widgetbox").attr("id");
    const $callPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .btn[data-preview-for="${widgetId}"] button`
    );
    // Update button title in mobile preview
    $callPreview.text(newTitle);
  };
  window.updateSendMessageButtonTitle = function (input) {
    const $input = $(input);
    const newTitle = $input.val().trim();
    const widgetId = $input.closest(".widgetbox").attr("id");
    const $smsPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .btn[data-preview-for="${widgetId}"] button`
    );
    // Update button title in mobile preview
    $smsPreview.text(newTitle);
  };
  window.updateLinkpageButtonTitle = function (input) {
    const $input = $(input);
    const newTitle = $input.val().trim();
    const widgetId = $input.closest(".widgetbox").attr("id");
    const $smsPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .btn[data-preview-for="${widgetId}"] button`
    );
    // Update button title in mobile preview
    $smsPreview.text(newTitle);
  }
  /**
 * Check if business is currently open based on timezone and hours
 * @param {string} timezone - IANA timezone (e.g., 'America/New_York')
 * @param {string} dayOfWeek - Day name (e.g., 'monday')
 * @param {boolean} isOpen - Whether business is marked as open
 * @param {string} startTime - Start time (e.g., '09:00 AM')
 * @param {string} endTime - End time (e.g., '06:00 PM')
 * @returns {object} - { isCurrentlyOpen: boolean, message: string, currentDay: string }
 */
  function checkBusinessHoursStatus(timezone, dayOfWeek, isOpen, startTime, endTime) {
    try {
      // Get current time in the selected timezone
      const now = new Date();
      const options = {
        timeZone: timezone,
        hour12: false,
        weekday: 'long',
        hour: '2-digit',
        minute: '2-digit'
      };

      const formatter = new Intl.DateTimeFormat('en-US', options);
      const parts = formatter.formatToParts(now);

      const currentDayName = parts.find(p => p.type === 'weekday').value.toLowerCase();
      const currentHour = parseInt(parts.find(p => p.type === 'hour').value);
      const currentMinute = parseInt(parts.find(p => p.type === 'minute').value);

      // Check if today matches the day of week we're checking
      if (currentDayName !== dayOfWeek.toLowerCase()) {
        return {
          isCurrentlyOpen: false,
          message: 'Closed (Different Day)',
          currentDay: currentDayName
        };
      }

      // If not marked as open for this day, return closed
      if (!isOpen) {
        return {
          isCurrentlyOpen: false,
          message: 'Closed',
          currentDay: currentDayName
        };
      }

      // Convert 12-hour time to 24-hour for comparison
      function convertTo24Hour(time) {
        const match = time.match(/(\d+):(\d+)\s*(AM|PM)/i);
        if (!match) return null;

        let hours = parseInt(match[1]);
        const minutes = parseInt(match[2]);
        const period = match[3].toUpperCase();

        if (period === 'PM' && hours !== 12) {
          hours += 12;
        } else if (period === 'AM' && hours === 12) {
          hours = 0;
        }

        return hours * 60 + minutes; // Convert to minutes since midnight
      }

      const currentTimeInMinutes = currentHour * 60 + currentMinute;
      const startTimeInMinutes = convertTo24Hour(startTime);
      const endTimeInMinutes = convertTo24Hour(endTime);

      if (startTimeInMinutes === null || endTimeInMinutes === null) {
        return { isCurrentlyOpen: false, message: 'Invalid Time Format' };
      }

      // Check if current time is within business hours
      const isWithinHours = currentTimeInMinutes >= startTimeInMinutes &&
        currentTimeInMinutes < endTimeInMinutes;

      return {
        isCurrentlyOpen: isWithinHours,
        message: isWithinHours ? `Closes ${endTime}` : 'Closed',
        startTime: startTime,
        endTime: endTime,
        currentDay: currentDayName
      };

    } catch (error) {
      console.error('Error checking business hours:', error);
      return {
        isCurrentlyOpen: false,
        message: 'Error Checking Hours',
        currentDay: 'unknown'
      };
    }
  }

  function updateBusinessHoursWidgetStatus(widgetId) {
    const $widget = $(`#${widgetId}`);
    if (!$widget.length) return;

    // Get selected timezone
    const timezone = $widget.find('.timezone-selection').val() || 'America/New_York';

    // Get business hours type
    const hoursType = $widget.find('input[name^="lp-w-bh-timezone-type"]:checked').val();

    // Get current day in the selected timezone
    const now = new Date();
    const options = { timeZone: timezone, weekday: 'long' };
    const currentDay = new Intl.DateTimeFormat('en-US', options).format(now).toLowerCase();

    console.log(`[Business Hours Check] Widget: ${widgetId}`);
    console.log(`Timezone: ${timezone}, Current Day: ${currentDay}, Type: ${hoursType}`);

    // Find the current day's section in the widget
    const $daySection = $widget.find(`.timezoecheckbox`).filter(function () {
      const dayCheckbox = $(this).find('input[type="checkbox"]');
      const dayValue = dayCheckbox.val();
      return dayValue && dayValue.toLowerCase() === currentDay;
    });

    if (!$daySection.length) {
      console.warn(`No section found for day: ${currentDay}`);
      return;
    }

    // Get day's settings
    const isOpen = $daySection.find('input[type="checkbox"]').is(':checked');
    const startTime = $daySection.find('.timestartinput').val() || '09:00 AM';
    const endTime = $daySection.find('.timeendinput').val() || '06:00 PM';

    console.log(`Day Settings - Open: ${isOpen}, Start: ${startTime}, End: ${endTime}`);

    // Check status
    const status = checkBusinessHoursStatus(timezone, currentDay, isOpen, startTime, endTime);

    console.log(`Status Result - Currently Open: ${status.isCurrentlyOpen}, Message: ${status.message}`);

    // Update preview
    const $preview = $(`.mobilelayoutmain #linkpage-id .hours-card[data-preview-for="${widgetId}"]`);

    if (!$preview.length) {
      console.warn(`No preview found for widget: ${widgetId}`);
      return;
    }

    // Update status text based on business type
    const $statusStrong = $preview.find('.hours-toggle .status-text strong');
    const $statusSmall = $preview.find('.hours-toggle .status-text small');
    const tzAbbr = getTimezoneAbbr(timezone);

    if (hoursType === 'opening-closing') {
      if (status.isCurrentlyOpen) {
        $statusStrong.text('OPEN NOW');
        $statusSmall.text(`Closes ${endTime} · ${startTime} to ${endTime} (${tzAbbr})`);
        $statusStrong.css('color', '#00ff00'); // Green for open
      } else {
        $statusStrong.text('CLOSED NOW');
        // Find next opening time
        const nextOpenInfo = findNextOpenTime($widget, timezone, currentDay);
        if (nextOpenInfo) {
          $statusSmall.text(`Opens ${nextOpenInfo.day === currentDay ? 'today' : nextOpenInfo.day} at ${nextOpenInfo.startTime} (${tzAbbr})`);
        } else {
          $statusSmall.text(`Currently Closed · ${startTime} to ${endTime} (${tzAbbr})`);
        }
        $statusStrong.css('color', '#ff0000'); // Red for closed
      }
    } else if (hoursType === 'online-delivery') {
      if (status.isCurrentlyOpen) {
        $statusStrong.text('OPEN FOR DELIVERY');
        $statusSmall.text(`Closes ${endTime} · ${startTime} to ${endTime} (${tzAbbr})`);
        $statusStrong.css('color', '#00ff00'); // Green for open
      } else {
        $statusStrong.text('CLOSED FOR DELIVERY');
        // Find next opening time
        const nextOpenInfo = findNextOpenTime($widget, timezone, currentDay);
        if (nextOpenInfo) {
          $statusSmall.text(`Opens ${nextOpenInfo.day === currentDay ? 'today' : nextOpenInfo.day} at ${nextOpenInfo.startTime} (${tzAbbr})`);
        } else {
          $statusSmall.text(`Currently Closed · ${startTime} to ${endTime} (${tzAbbr})`);
        }
        $statusStrong.css('color', '#ff0000'); // Red for closed
      }
    } else if (hoursType === 'check-in-checkout') {
      // if (status.isCurrentlyOpen) {
      //   $statusStrong.text('OPEN FOR DELIVERY');
      //   $statusSmall.text(`Closes ${endTime} · ${startTime} to ${endTime} (${tzAbbr})`);
      //   $statusStrong.css('color', '#00ff00'); // Green for open
      // } else {
      //   $statusStrong.text('CLOSED FOR DELIVERY');
      //   // Find next opening time
      //   const nextOpenInfo = findNextOpenTime($widget, timezone, currentDay);
      //   if (nextOpenInfo) {
      //     $statusSmall.text(`Opens ${nextOpenInfo.day === currentDay ? 'today' : nextOpenInfo.day} at ${nextOpenInfo.startTime} (${tzAbbr})`);
      //   } else {
      //     $statusSmall.text(`Currently Closed · ${startTime} to ${endTime} (${tzAbbr})`);
      //   }
      //   $statusStrong.css('color', '#ff0000'); // Red for closed
      // }
      $statusSmall.html(`CHECK IN: ${startTime}<br>CHECK OUT: ${endTime}`);
    }

    // Update the hours list to highlight current day
    $preview.find('.hours-content ul li').removeClass('current-day');
    $preview.find(`.hours-content ul li[data-day="${currentDay}"]`).addClass('current-day');
  }

  /**
 * Find the next opening time
 */
  function findNextOpenTime($widget, timezone, currentDay) {
    const daysOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    const currentIndex = daysOrder.indexOf(currentDay);

    // Check remaining days of the week
    for (let i = 1; i < 7; i++) {
      const nextIndex = (currentIndex + i) % 7;
      const nextDay = daysOrder[nextIndex];

      const $nextDaySection = $widget.find(`.timezoecheckbox`).filter(function () {
        const dayCheckbox = $(this).find('input[type="checkbox"]');
        const dayValue = dayCheckbox.val();
        return dayValue && dayValue.toLowerCase() === nextDay;
      });

      if ($nextDaySection.length) {
        const isOpen = $nextDaySection.find('input[type="checkbox"]').is(':checked');
        if (isOpen) {
          const startTime = $nextDaySection.find('.timestartinput').val() || '09:00 AM';
          return {
            day: nextDay.charAt(0).toUpperCase() + nextDay.slice(1),
            startTime: startTime
          };
        }
      }
    }

    return null;
  }

  /**
 * Get timezone abbreviation (EST, PST, etc.)
 */
  function getTimezoneAbbr(timezone) {
    try {
      const date = new Date();
      const shortFormat = new Intl.DateTimeFormat('en-US', {
        timeZone: timezone,
        timeZoneName: 'short'
      });
      const parts = shortFormat.formatToParts(date);
      const tzPart = parts.find(part => part.type === 'timeZoneName');
      return tzPart ? tzPart.value : 'Local';
    } catch {
      return 'Local';
    }
  }
  window.updateBusinessHoursTitle = function (input) {
    const $input = $(input);
    const newTitle = $input.val().trim();
    const widgetId = $input.closest(".widgetbox").attr("id");

    const $widgetBox = $input.closest(".widgetbox");
    $widgetBox.find(".timezoneallweeks").hide();
    $widgetBox.find(".timezoneweekdays").hide();

    const $hoursCard = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .hours-card[data-preview-for="${widgetId}"]`
    );

    if (newTitle === "opening-closing") {
      $widgetBox.find(".timezoneweekdays").show();
      $widgetBox.find("#sametimezone1").prop("checked", false).prop("disabled", false);
      $hoursCard.find(".hours-toggle .status-text strong").text("OPEN NOW");
      $hoursCard.find(".hours-content").show();
      $hoursCard.find(".arrow, .icon, strong").show();
      $hoursCard.find(".hours-toggle .hours-left .icon").html('<i class="fa-solid fa-xs fa-clock"></i>');
      $hoursCard.find(".hours-toggle .hours-left").css('gap', '10px');

      // Update status based on current time
      updateBusinessHoursWidgetStatus(widgetId);
    }
    else if (newTitle === "online-delivery") {
      $widgetBox.find(".timezoneweekdays").show();
      $widgetBox.find("#sametimezone1").prop("checked", false).prop("disabled", false);
      $hoursCard.find(".hours-toggle .status-text strong").text("OPEN FOR DELIVERY");
      $hoursCard.find(".hours-content").show();
      $hoursCard.find(".arrow, .icon, strong").show();
      $hoursCard.find(".hours-toggle .hours-left .icon").html('<i class="fa-solid fa-xs fa-truck"></i>');
      $hoursCard.find(".hours-toggle .hours-left").css('gap', '10px');

      // Update status based on current time
      updateBusinessHoursWidgetStatus(widgetId);
    }
    else if (newTitle === "check-in-checkout") {
      $widgetBox.find(".timezoneallweeks").show();
      $widgetBox.find("#sametimezone1").prop("checked", true).prop("disabled", true);

      $hoursCard.find(".hours-content").hide();
      $hoursCard.find(".hours-toggle .arrow").hide();
      $hoursCard.find(".hours-toggle .hours-left .icon").html('<i class="fa-solid fa-xs fa-clock"></i>');
      $hoursCard.find(".hours-toggle .hours-left").css('gap', '40px');
      $hoursCard.find(".hours-toggle .hours-left .status-text strong").hide();

      const startTime = $widgetBox.find('.timezoneallweeks .timestartinput').val() || '09:00 AM';
      const endTime = $widgetBox.find('.timezoneallweeks .timeendinput').val() || '06:00 PM';

      $hoursCard.find(".hours-toggle .status-text small")
        .html(`CHECK IN: ${startTime}<br>CHECK OUT: ${endTime}`);
    }
  };
  // Business Hours Toggle Handler
  // window.updateBusinessHoursTitle = function (input) {
  //   const $input = $(input);
  //   const newTitle = $input.val().trim();
  //   const widgetId = $input.closest(".widgetbox").attr("id");

  //   // Hide all week sections initially
  //   const $widgetBox = $input.closest(".widgetbox");
  //   $widgetBox.find(".timezoneallweeks").hide();
  //   $widgetBox.find(".timezoneweekdays").hide();

  //   const $hoursCard = $(
  //     `.mobilelayoutmain #linkpage-id .linkpage-body .hours-card[data-preview-for="${widgetId}"]`
  //   );

  //   if (newTitle === "opening-closing") {
  //     $widgetBox.find(".timezoneweekdays").show();
  //     $widgetBox.find("#sametimezone1").prop("checked", false).prop("disabled", false);
  //     $hoursCard.find(".hours-toggle .status-text strong").text("OPEN NOW");
  //     $hoursCard.find(".hours-content").show();
  //     $hoursCard.find(".arrow, .icon, strong").show();
  //     $hoursCard.find(".hours-toggle .hours-left .icon").html('<i class="fa-solid fa-xs fa-clock"></i>');
  //     $hoursCard.find(".hours-toggle .hours-left").css('gap', '10px');

  //     $hoursCard.find(".hours-toggle .status-text small")
  //       .text(`Closes 6:00 PM · 9:00 AM to 6:00 PM (IST)`);
  //   }

  //   else if (newTitle === "online-delivery") {
  //     $widgetBox.find(".timezoneweekdays").show();
  //     $widgetBox.find("#sametimezone1").prop("checked", false).prop("disabled", false);
  //     $hoursCard.find(".hours-toggle .status-text strong").text("OPEN FOR DELIVERY");
  //     $hoursCard.find(".hours-content").show();
  //     $hoursCard.find(".arrow, .icon, strong").show();
  //     $hoursCard.find(".hours-toggle .hours-left .icon").html('<i class="fa-solid fa-xs fa-truck"></i>');
  //     $hoursCard.find(".hours-toggle .hours-left").css('gap', '10px');

  //     $hoursCard.find(".hours-toggle .status-text small")
  //       .text(`Closes 6:00 PM · 9:00 AM to 6:00 PM (IST)`);
  //   }

  //   else if (newTitle === "check-in-checkout") {
  //     $widgetBox.find(".timezoneallweeks").show();
  //     $widgetBox.find("#sametimezone1").prop("checked", true).prop("disabled", true);

  //     // Hide elements
  //     $hoursCard.find(".hours-content").hide();
  //     $hoursCard.find(".hours-toggle .arrow").hide();
  //     $hoursCard.find(".hours-toggle .hours-left .icon").html('<i class="fa-solid fa-xs fa-clock"></i>');
  //     $hoursCard.find(".hours-toggle .hours-left").css('gap', '40px');
  //     $hoursCard.find(".hours-toggle .hours-left .status-text strong").hide();

  //     $hoursCard.find(".hours-toggle .status-text small")
  //       .html(`CHECK IN: 09:00 AM<br>CHECK OUT: 06:00 PM`);
  //   }
  // };

  // window.updateBusinessTimeToggle = function (checkbox) {
  //   console.log("Business hours toggle changed");
  //   const $checkbox = $(checkbox);
  //   const isOpen = $checkbox.is(":checked");
  //   const widgetId = $checkbox.closest(".widgetbox").attr("id");
  //   const day = $checkbox.val();
  //   const $section = $checkbox.closest(".timezoecheckbox");

  //   // Extract times from this specific section
  //   const startTime = $section.find('.timestartinput').val() || "09:00 AM";
  //   const endTime = $section.find('.timeendinput').val() || "06:00 PM";

  //   // Detect widget ID & day
  //   const $hoursPreview = $(
  //     `.mobilelayoutmain #linkpage-id .linkpage-body .hours-card[data-preview-for="${widgetId}"] .hours-content ul li[data-day="${day}"] .status`
  //   );
  //   // Update open/closed status in mobile preview
  //   $hoursPreview.text(isOpen ? `${startTime} - ${endTime}` : "Closed");
  // };
  // window.updateBusinessTimeStart = function (input) {
  //   console.log("Business start time changed");
  //   const $input = $(input);
  //   const startTime = $input.val().trim() || "09:00 AM";
  //   const widgetId = $input.closest(".widgetbox").attr("id");
  //   const day = $input.closest(".timezoecheckbox").find('input[type="checkbox"]').val();
  //   const $hoursPreview = $(
  //     `.mobilelayoutmain #linkpage-id .linkpage-body .hours-card[data-preview-for="${widgetId}"] .hours-content ul li[data-day="${day}"] .status`
  //   );
  //   // Get current end time from the corresponding input
  //   const endTime = $input.closest(".timezoecheckbox").find('.timeendinput').val() || "06:00 PM";
  //   // Update time range in mobile preview
  //   const isOpen = $input.closest(".timezoecheckbox").find('input[type="checkbox"]').is(":checked");
  //   $hoursPreview.text(isOpen ? `${startTime} - ${endTime}` : "Closed");

  //   // const $hoursCard = $(
  //   //   `.mobilelayoutmain #linkpage-id .linkpage-body .hours-card[data-preview-for="${widgetId}"] .hours-toggle .status-text small`
  //   // );
  //   // $hoursCard.html(`${startTime} to ${endTime} (IST)`);
  // };
  // window.updateBusinessTimeEnd = function (input) {
  //   console.log("Business end time changed");
  //   const $input = $(input);
  //   const endTime = $input.val().trim() || "06:00 PM";
  //   const widgetId = $input.closest(".widgetbox").attr("id");
  //   const day = $input.closest(".timezoecheckbox").find('input[type="checkbox"]').val();
  //   const $hoursPreview = $(
  //     `.mobilelayoutmain #linkpage-id .linkpage-body .hours-card[data-preview-for="${widgetId}"] .hours-content ul li[data-day="${day}"] .status`
  //   );
  //   // Get current start time from the corresponding input
  //   const startTime = $input.closest(".timezoecheckbox").find('.timestartinput').val() || "09:00 AM";
  //   // Update time range in mobile preview
  //   const isOpen = $input.closest(".timezoecheckbox").find('input[type="checkbox"]').is(":checked");
  //   $hoursPreview.text(isOpen ? `${startTime} - ${endTime}` : "Closed");

  //   // const $hoursCard = $(
  //   //   `.mobilelayoutmain #linkpage-id .linkpage-body .hours-card[data-preview-for="${widgetId}"] .hours-toggle .status-text small`
  //   // );
  //   // $hoursCard.html(`${startTime} to ${endTime} (IST)`);
  // };
  // Helper function to update all days preview with same time
  function updateAllDaysPreview(widgetId, startTime, endTime) {
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    days.forEach(day => {
      const $hoursPreview = $(
        `.mobilelayoutmain #linkpage-id .hours-card[data-preview-for="${widgetId}"] .hours-content ul li[data-day="${day}"] .status`
      );
      $hoursPreview.text(`${startTime} - ${endTime}`);
    });
  }

  // UPDATED: Handle "Same for all days" checkbox
  window.updateSameForAllDays = function (checkbox) {
    const $checkbox = $(checkbox);
    const isChecked = $checkbox.is(":checked");
    const $widget = $checkbox.closest(".widgetbox");
    const widgetId = $widget.attr("id");

    const $weekdaysSection = $widget.find(".timezoneweekdays");
    const $allWeeksSection = $widget.find(".timezoneallweeks");

    if (isChecked) {
      // Hide individual days, show "Mon - Sun" section
      $weekdaysSection.hide();
      $allWeeksSection.show();

      // Get the time from the all-week section
      const startTime = $allWeeksSection.find('.timestartinput').val() || "09:00 AM";
      const endTime = $allWeeksSection.find('.timeendinput').val() || "06:00 PM";

      // Disable all individual day toggles and set them to open
      $weekdaysSection.find('input[type="checkbox"]').each(function () {
        $(this).prop("checked", true).prop("disabled", true);

        // Update each day's time inputs to match
        const $daySection = $(this).closest(".timezoecheckbox");
        $daySection.find('.timestartinput').val(startTime);
        $daySection.find('.timeendinput').val(endTime);
      });

      // Update preview to show all days with same time
      updateAllDaysPreview(widgetId, startTime, endTime);

    } else {
      // Show individual days, hide "Mon - Sun" section
      $weekdaysSection.show();
      $allWeeksSection.hide();

      // Enable all individual day toggles
      $weekdaysSection.find('input[type="checkbox"]').prop("disabled", false);

      // Update each day's preview based on its individual settings
      $weekdaysSection.find('.timezoecheckbox').each(function () {
        const $daySection = $(this);
        const day = $daySection.find('input[type="checkbox"]').val();
        const isOpen = $daySection.find('input[type="checkbox"]').is(':checked');
        const startTime = $daySection.find('.timestartinput').val() || "09:00 AM";
        const endTime = $daySection.find('.timeendinput').val() || "06:00 PM";

        const $hoursPreview = $(
          `.mobilelayoutmain #linkpage-id .hours-card[data-preview-for="${widgetId}"] .hours-content ul li[data-day="${day}"] .status`
        );
        $hoursPreview.text(isOpen ? `${startTime} - ${endTime}` : "Closed");
      });
    }

    // Update real-time status
    updateBusinessHoursWidgetStatus(widgetId);
  };

  // UPDATED: Business Hours Toggle Handler
  window.updateBusinessTimeToggle = function (checkbox) {
    console.log("Business hours toggle changed");
    const $checkbox = $(checkbox);
    const isOpen = $checkbox.is(":checked");
    const widgetId = $checkbox.closest(".widgetbox").attr("id");
    const day = $checkbox.val();
    const $section = $checkbox.closest(".timezoecheckbox");

    // Extract times from this specific section
    const startTime = $section.find('.timestartinput').val() || "09:00 AM";
    const endTime = $section.find('.timeendinput').val() || "06:00 PM";

    // Update the label
    const label = $section.find(".switchbox .label-text");
    label.text(isOpen ? "Open" : "Close");

    // Enable/disable time inputs
    const $startInput = $section.find(".timestartinput");
    const $endInput = $section.find(".timeendinput");

    if (isOpen) {
      $startInput.prop("disabled", false);
      $endInput.prop("disabled", false);
    } else {
      $startInput.prop("disabled", true);
      $endInput.prop("disabled", true);
    }

    // Update preview in mobile view
    const $hoursPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .hours-card[data-preview-for="${widgetId}"] .hours-content ul li[data-day="${day}"] .status`
    );
    $hoursPreview.text(isOpen ? `${startTime} - ${endTime}` : "Closed");

    // IMPORTANT: Update the real-time status
    updateBusinessHoursWidgetStatus(widgetId);
  };
  // UPDATED: Business Hours Start Time Handler
  window.updateBusinessTimeStart = function (input) {
    console.log("Business start time changed");
    const $input = $(input);
    const startTime = $input.val().trim() || "09:00 AM";
    const widgetId = $input.closest(".widgetbox").attr("id");
    const day = $input.closest(".timezoecheckbox").find('input[type="checkbox"]').val();

    // Get current end time from the corresponding input
    const endTime = $input.closest(".timezoecheckbox").find('.timeendinput').val() || "06:00 PM";

    // Validate time order
    if (!isValidTimeOrder(startTime, endTime)) {
      alert("Start time must be before end time");
      $input.val("09:00 AM");
      return;
    }

    // Update time range in mobile preview
    const isOpen = $input.closest(".timezoecheckbox").find('input[type="checkbox"]').is(":checked");
    const $hoursPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .hours-card[data-preview-for="${widgetId}"] .hours-content ul li[data-day="${day}"] .status`
    );
    $hoursPreview.text(isOpen ? `${startTime} - ${endTime}` : "Closed");

    // IMPORTANT: Update the real-time status
    updateBusinessHoursWidgetStatus(widgetId);
  };

  // UPDATED: Business Hours End Time Handler
  window.updateBusinessTimeEnd = function (input) {
    console.log("Business end time changed");
    const $input = $(input);
    const endTime = $input.val().trim() || "06:00 PM";
    const widgetId = $input.closest(".widgetbox").attr("id");
    const day = $input.closest(".timezoecheckbox").find('input[type="checkbox"]').val();

    // Get current start time from the corresponding input
    const startTime = $input.closest(".timezoecheckbox").find('.timestartinput').val() || "09:00 AM";

    // Validate time order
    if (!isValidTimeOrder(startTime, endTime)) {
      alert("End time must be after start time");
      $input.val("06:00 PM");
      return;
    }

    // Update time range in mobile preview
    const isOpen = $input.closest(".timezoecheckbox").find('input[type="checkbox"]').is(":checked");
    const $hoursPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .hours-card[data-preview-for="${widgetId}"] .hours-content ul li[data-day="${day}"] .status`
    );
    $hoursPreview.text(isOpen ? `${startTime} - ${endTime}` : "Closed");

    // IMPORTANT: Update the real-time status
    updateBusinessHoursWidgetStatus(widgetId);
  };
  // UPDATED: Handle all-week time changes
  window.updateAllWeekTimeStart = function (input) {
    const $input = $(input);
    const startTime = $input.val().trim() || "09:00 AM";
    const widgetId = $input.closest(".widgetbox").attr("id");
    const $widget = $input.closest(".widgetbox");

    // Get end time from all-week section
    const endTime = $input.closest(".timezoneallweeks").find('.timeendinput').val() || "06:00 PM";

    // Validate time order
    if (!isValidTimeOrder(startTime, endTime)) {
      alert("Start time must be before end time");
      $input.val("09:00 AM");
      return;
    }

    // Update all individual day sections with this time
    $widget.find(".timezoneweekdays .timezoecheckbox").each(function () {
      $(this).find('.timestartinput').val(startTime);
    });

    // Update preview for all days
    updateAllDaysPreview(widgetId, startTime, endTime);

    // Update real-time status
    updateBusinessHoursWidgetStatus(widgetId);
  };

  window.updateAllWeekTimeEnd = function (input) {
    const $input = $(input);
    const endTime = $input.val().trim() || "06:00 PM";
    const widgetId = $input.closest(".widgetbox").attr("id");
    const $widget = $input.closest(".widgetbox");

    // Get start time from all-week section
    const startTime = $input.closest(".timezoneallweeks").find('.timestartinput').val() || "09:00 AM";

    // Validate time order
    if (!isValidTimeOrder(startTime, endTime)) {
      alert("End time must be after start time");
      $input.val("06:00 PM");
      return;
    }

    // Update all individual day sections with this time
    $widget.find(".timezoneweekdays .timezoecheckbox").each(function () {
      $(this).find('.timeendinput').val(endTime);
    });

    // Update preview for all days
    updateAllDaysPreview(widgetId, startTime, endTime);

    // Update real-time status
    updateBusinessHoursWidgetStatus(widgetId);
  };
  /**
   * Validate that start time is before end time
   */
  /**
  * Validate that start time is before end time
  */
  function isValidTimeOrder(startTime, endTime) {
    function convertTo24Hour(time) {
      const match = time.match(/(\d+):(\d+)\s*(AM|PM)/i);
      if (!match) return null;

      let hours = parseInt(match[1]);
      const minutes = parseInt(match[2]);
      const period = match[3].toUpperCase();

      if (period === 'PM' && hours !== 12) {
        hours += 12;
      } else if (period === 'AM' && hours === 12) {
        hours = 0;
      }

      return hours * 60 + minutes;
    }

    const startMinutes = convertTo24Hour(startTime);
    const endMinutes = convertTo24Hour(endTime);

    if (startMinutes === null || endMinutes === null) {
      return true; // Can't validate, allow it
    }

    return startMinutes < endMinutes;
  }
  // ADD event listener for timezone changes
  $(document).on('change', '.timezone-selection', function () {
    const widgetId = $(this).closest('.widgetbox').attr('id');
    updateBusinessHoursWidgetStatus(widgetId);
  });

  // ADD event listeners for time changes
  $(document).on('change', '.timestartinput, .timeendinput', function () {
    const widgetId = $(this).closest('.widgetbox').attr('id');
    updateBusinessHoursWidgetStatus(widgetId);
  });

  // INITIALIZE status on page load for all business hours widgets
  $(document).ready(function () {
    console.log('Initializing business hours status checks...');

    $('.business-hours').each(function () {
      const widgetId = $(this).attr('id');
      if (widgetId) {
        console.log(`Initializing widget: ${widgetId}`);
        updateBusinessHoursWidgetStatus(widgetId);
      }
    });

    // Update every minute to keep status current
    setInterval(function () {
      console.log('Running scheduled business hours update...');
      $('.business-hours').each(function () {
        const widgetId = $(this).attr('id');
        if (widgetId) {
          updateBusinessHoursWidgetStatus(widgetId);
        }
      });
    }, 60000); // Check every 60 seconds
  });

  // Export functions for external use
  window.checkBusinessHoursStatus = checkBusinessHoursStatus;
  window.updateBusinessHoursWidgetStatus = updateBusinessHoursWidgetStatus;
  window.updateProfileLayout = function (select) {
    const $select = $(select);
    const newLayout = $select.val();
    const widgetId = $select.closest(".widgetbox").attr("id");
    if (newLayout == 'profile') {
      $('.cust-profilefild').show();
      $('.profileimgdiv').show();
      $('.uploaddivforoption2').hide();
      $('#linkpage-header-id').removeClass('linkpage-header');
      $('#linkpage-header-id').removeClass('linkpage-header-full');
      $('.linkpage-thumbnail .cover-page-section').hide();
      $('.linkpage-thumbnail .img-box').show();
    } else if (newLayout == 'banner') {
      $('.cust-profilefild').show();
      $('.profileimgdiv').show();
      $('.uploaddivforoption2').show();
      $('#linkpage-header-id').addClass('linkpage-header');
      $('#linkpage-header-id').removeClass('linkpage-header-full');
      $('.linkpage-thumbnail .cover-page-section').hide();
      $('.linkpage-thumbnail .img-box').show();
    } else if (newLayout == 'cover') {
      $('.cust-profilefild').show();
      $('.profileimgdiv').hide();
      $('.uploaddivforoption2').show();
      $('#linkpage-header-id').addClass('linkpage-header');
      $('#linkpage-header-id').removeClass('linkpage-header-full');
      $('.linkpage-thumbnail .cover-page-section').show();
      $('.linkpage-thumbnail .img-box').hide();
    } else if (newLayout == 'minimal') {
      $('.cust-profilefild').hide();
      $('.profileimgdiv').hide();
      $('.uploaddivforoption2').hide();
      $('#linkpage-header-id').removeClass('linkpage-header');
      $('#linkpage-header-id').removeClass('linkpage-header-full');
      $('.linkpage-thumbnail .cover-page-section').hide();
      $('.linkpage-thumbnail .img-box').hide();
    }
    const $profilePreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .profile-card[data-preview-for="${widgetId}"]`
    );
    // Update profile card layout in mobile preview
    $profilePreview.attr("data-layout", newLayout);
  }
  window.updateLayoutBackgroundWidth = function (checkbox) {
    const $checkbox = $(checkbox);
    const isFullWidth = $checkbox.is(":checked");
    if (isFullWidth) {
      $('#linkpage-header-id').addClass('linkpage-header-full');
      $('#linkpage-header-id').removeClass('linkpage-header');
    } else {
      $('#linkpage-header-id').addClass('linkpage-header');
      $('#linkpage-header-id').removeClass('linkpage-header-full');
    }
  }

  window.updateAddButtonWidgetText = function (input) {
    console.log("Update Add Button Widget Text");
    const $input = $(input);
    const newText = $input.val().trim();
    const widgetId = $input.closest(".widgetbox").attr("id");
    console.log("Widget ID:", widgetId);
    const $buttonPreview = $(
      `.mobilelayoutmain #linkpage-id .btn[data-preview-for="${widgetId}"] button`
    );

    // Update button text in mobile preview
    $buttonPreview.text(newText);
  };
  window.updateButtonWidgetPreviewType = function (radio) {
    const $radio = $(radio);
    const newText = $radio.val();
    const widgetId = $radio.closest(".widgetbox").attr("id");
    console.log("Widget ID:", widgetId);
    const $buttonPreview = $(
      `.mobilelayoutmain #linkpage-id .btn[data-preview-for="${widgetId}"]`
    );
    if (newText == 'No Images') {
      $buttonPreview.removeClass('btn-with-icon');
      $buttonPreview.removeClass('btn-with-thumbnail');
    } else if (newText == 'Icons') {
      $buttonPreview.addClass('btn-with-icon');
      $buttonPreview.removeClass('btn-with-thumbnail');
    } else if (newText == 'Thumbnail') {
      $buttonPreview.removeClass('btn-with-icon');
      $buttonPreview.addClass('btn-with-thumbnail');
    }
    // Update button text in mobile preview
  }
  window.updateAddButtonWidgetURL = function (input) {
    const $input = $(input);
    const newURL = $input.val().trim();
    const widgetId = $input.closest(".widgetbox").attr("id");
    const $buttonPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .btn[data-preview-for="${widgetId}"] button`
    );
    // Update button URL in mobile preview
    $buttonPreview.attr("data-url", newURL);
  };

  window.updateBackgroundColor = function (input) {
    const $input = $(input);
    const newColor = $input.val().trim();
    const widgetId = $input.closest(".widgetbox").attr("id");
    const $widgetPreview = $(
      `.mobilelayoutmain .mobiledevice`
    );
    // Update background color in mobile preview
    $widgetPreview.css("background-color", newColor);
  };
  window.updateButtonStyle = function (select) {
    // const $select = $(select);
    // const newStyle = $select.val();
    // const widgetId = $select.closest(".widgetbox").attr("id");
    const $buttonPreview = $(
      `.mobilelayoutmain #linkpage-id .btn`
    );
    const $businessPreview = $(
      `.mobilelayoutmain #linkpage-id .hours-card`
    )
    // Update button style in mobile preview
    if (select == 'square') {
      $buttonPreview.removeClass('rounded-filled').removeClass('rounded-outlined').removeClass('square-outlined').addClass('square-filled');
      $businessPreview.removeClass('rounded-filled').removeClass('rounded-outlined').removeClass('square-outlined').addClass('square-filled');

    }
    else if (select == 'rounded') {
      $buttonPreview.addClass('rounded-filled').removeClass('rounded-outlined').removeClass('square-outlined').removeClass('square-filled');
      $businessPreview.addClass('rounded-filled').removeClass('rounded-outlined').removeClass('square-outlined').removeClass('square-filled');
    }
    else if (select == 'outline-square') {
      $buttonPreview.removeClass('rounded-filled').removeClass('rounded-outlined').addClass('square-outlined').removeClass('square-filled');
      $businessPreview.removeClass('rounded-filled').removeClass('rounded-outlined').addClass('square-outlined').removeClass('square-filled');
    }
    else if (select == 'outline-rounded') {
      $buttonPreview.removeClass('rounded-filled').addClass('rounded-outlined').removeClass('square-outlined').removeClass('square-filled');
      $businessPreview.removeClass('rounded-filled').addClass('rounded-outlined').removeClass('square-outlined').removeClass('square-filled');
    }
  };
  window.updateButtonColor = function (input) {
    const $input = $(input);
    const newColor = $input.val().trim();
    // Update button color in mobile preview
    document.documentElement.style.setProperty('--btn-bg-color', newColor);
  }
  window.updateButtonTextColor = function (input) {
    const $input = $(input);
    const newColor = $input.val().trim();
    $buttonPreview = $(
      `.mobilelayoutmain #linkpage-id .btn button`
    );
    $buttonPreview.css("color", newColor);
    document.documentElement.style.setProperty('--btn-text-color', newColor);
  }
  window.updateFontFamily = function (select) {
    const $select = $(select);
    const newFont = $select.val();
    document.documentElement.style.setProperty('--preview-font-family', newFont);
  }
  window.updateGallaryLayout = function (radio) {
    const $radio = $(radio);
    const newText = $radio.val();
    const widgetId = $radio.closest(".widgetbox").attr("id");
    console.log("Widget ID:", widgetId);
    const $gridView = $(
      `.mobilelayoutmain #linkpage-id .image-gallery-view[data-preview-for="${widgetId}"] .image-grid-view`
    );
    const $carouselView = $(
      `.mobilelayoutmain #linkpage-id .image-gallery-view[data-preview-for="${widgetId}"] .image-carousel-view`
    );
    if (newText == 'grid') {
      $gridView.show();
      $carouselView.hide();
    } else {
      $gridView.hide();
      $carouselView.show();
    }
  }
  window.updateBackgroundType = function (radio) {
    const $radio = $(radio);
    const newText = $radio.val();
    const mobile = document.querySelector('.mobiledevice');
    if (newText == 'solid') {
      $('.background-colorpicker').show();
      $('.imagepicker').hide();
      $('.videopicker').hide();
      mobile.style.setProperty('background-image', 'none');
    } else if (newText == 'image') {
      $('.background-colorpicker').hide();
      $('.imagepicker').show();
      $('.videopicker').hide();
      mobile.style.setProperty('background-image', 'var(--preview-background-image)');
    } else {
      $('.background-colorpicker').hide();
      $('.imagepicker').hide();
      $('.videopicker').show();
      mobile.style.setProperty('background-image', 'var(--preview-background-video)');
    }
  }
  // File upload preview handler
  // $(document).on("change", '.cust-upload-box input[type="file"]', function () {
  //   const file = this.files[0];
  //   const $box = $(this).closest(".cust-upload-box");

  //   if (!file) return;

  //   const reader = new FileReader();
  //   reader.onload = function (e) {
  //     $box.find(".cover-preview img").attr("src", e.target.result);
  //     $box.find(".cover-preview").show();
  //     $box.find(".upload-placeholder").hide();
  //   };
  //   reader.readAsDataURL(file);
  // });

  // Remove uploaded file
  // $(document).on("click", ".cust-upload-box .remove-btn", function () {
  //   const $box = $(this).closest(".cust-upload-box");
  //   $box.find('input[type="file"]').val("");
  //   $box.find(".cover-preview img").attr("src", "");
  //   $box.find(".cover-preview").hide();
  //   $box.find(".upload-placeholder").show();
  // });

  // Initialize Sortable for widget container
  if ($(".appendwidgetboxcontainer").length) {
    Sortable.create($(".appendwidgetboxcontainer")[0], {
      animation: 150,
      handle: ".movewidget i",
      ghostClass: "dragging",
    });
  }

  // Add Link Button Handler
  let counter = 2;
  $(".tabcontent .addbtn #addlink").on("click", function () {
    if ($("#buttontextfields").length) {
      const clone = $("#buttontextfields")
        .clone(true, true)
        .attr("id", "buttontextfields_" + counter)
        .show();

      // Update radio button IDs
      clone.find("input[type='radio']").each(function (index) {
        const oldId = $(this).attr("id");
        const newId = oldId + "_" + counter;
        $(this).attr("id", newId);
        clone.find("[for='" + oldId + "']").attr("for", newId);
      });

      // Update file input IDs
      clone.find("input[type='file']").each(function (index) {
        const oldId = $(this).attr("id");
        const newId = oldId + "_" + counter;
        $(this).attr("id", newId);
        clone.find("[for='" + oldId + "']").attr("for", newId);
      });

      // Update input names with counter
      clone
        .find("input[type='radio'],input[type='text'],input[type='range']")
        .each(function () {
          const originalName = $(this).attr("name");
          if (originalName) {
            const baseName = originalName.replace(/\[\d*\]$/, "");
            $(this).attr("name", baseName + "[" + counter + "]");
          }
        });

      // Clear input values in clone
      // clone.find("input[type='text']").val("");

      clone.appendTo(".appendwidgetboxcontainer");
      const newDropArea = clone.find(".drop-area")[0];
      if (newDropArea) {
        initDropArea(newDropArea);
      }
      create_preview("buttontextfields_" + counter);
    }

    counter++;
  });

  // Toggle widget content
  $(".appendwidgetboxcontainer").on("click", ".togglearrow i", function () {
    $(this)
      .closest(".cust-wraper")
      .find(".togglewhitebox")
      .toggleClass("togglehidden");
    $(this).toggleClass("fa-angle-up fa-angle-down");
  });

  // Delete widget (also remove its preview)
  $(".appendwidgetboxcontainer").on("click", ".deletebox i", function () {
    const $wrapper = $(this).closest(".cust-wraper");
    const widgetId = $wrapper.attr("id");
    // remove associated preview element
    if (widgetId) {
      $(".mobilelayoutmain #linkpage-id .linkpage-body")
        .find("[data-preview-for='" + widgetId + "']")
        .remove();
    }
    $wrapper.remove();
  });

  // Live-sync Separator widget settings to preview
  $(document).on(
    "input change",
    '.appendwidgetboxcontainer .Separatorfield .lp-w-separator-hexcolor, .appendwidgetboxcontainer .Separatorfield .lp-w-separator-color, .appendwidgetboxcontainer .Separatorfield input[name^="lp-w-separator-t-number"], .appendwidgetboxcontainer .Separatorfield input[id^="lp-w-separator-t-range"]',
    function () {
      const $control = $(this);
      const $widget = $control.closest(".cust-wraper");
      if (!$widget.length) return;
      const widgetId = $widget.attr("id");
      if (!widgetId) return;

      const $preview = $(".mobilelayoutmain #linkpage-id .linkpage-body").find(
        "[data-preview-for='" + widgetId + "']"
      );
      if (!$preview.length) return;

      // Color (prefer color picker, fall back to hex input)
      const colorPickerVal = $widget.find(".lp-w-separator-color").val();
      const hexVal = $widget.find(".lp-w-separator-hexcolor").val();
      const color = colorPickerVal || hexVal || "#000";

      // Thickness (number input controls height)
      const $num = $widget.find('input[name^="lp-w-separator-t-number"]');
      let thickness = parseInt($num.val() || 1, 10);
      if (isNaN(thickness) || thickness < 1) thickness = 1;

      // Width (range slider controls width percentage)
      const $range = $widget.find('input[id^="lp-w-separator-t-range"]');
      let widthVal = parseInt($range.val(), 10);
      if (isNaN(widthVal)) {
        // default to full width
        widthVal = 100;
      }
      // clamp width 0-100
      if (widthVal < 0) widthVal = 0;
      if (widthVal > 100) widthVal = 100;

      // Apply styles to the preview line: color, height (thickness), and width (as percent)
      const $line = $preview.find(".mid-line");
      if ($line.length) {
        $line.css({
          background: color,
          height: thickness + "px",
          width: widthVal + "%",
          margin: "0 auto",
          display: "block",
        });
      }
    }
  );

  // Add Widget Handler
  $(".addbtn .selectitems").on("click", function () {
    var targetId = $(this).data("id");

    // Find all existing widgets of this type
    var existingWidgets = $(
      ".appendwidgetboxcontainer .widgetbox[data-widget-type='" +
      $("#" + targetId).attr("data-widget-type") +
      "']"
    );

    var w_counter = existingWidgets.length + 1;

    console.log("w_counter:", w_counter);
    console.log("targetId:", targetId);

    if ($("#" + targetId).length) {
      const clone_w = $("#" + targetId)
        .clone(true, true)
        .attr("id", targetId + "_" + w_counter)
        .attr("data-widget-counter", w_counter)
        .show();

      // Update radio button IDs and names
      clone_w.find("input[type='radio']").each(function () {
        const w_oldId = $(this).attr("id");
        if (w_oldId) {
          const w_newId = w_oldId + "_" + w_counter;
          $(this).attr("id", w_newId);
          clone_w.find("[for='" + w_oldId + "']").attr("for", w_newId);
        }

        const originalName = $(this).attr("name");
        if (originalName) {
          const baseName = originalName.replace(/\[\d*\]$/, "");
          $(this).attr("name", baseName + "[" + w_counter + "]");
        }
      });

      // Update file input IDs
      clone_w.find("input[type='file']").each(function () {
        const w_oldId = $(this).attr("id");
        if (w_oldId) {
          const w_newId = w_oldId + "_" + w_counter;
          $(this).attr("id", w_newId);
          clone_w.find("[for='" + w_oldId + "']").attr("for", w_newId);
        }

        const originalName = $(this).attr("name");
        if (originalName) {
          const baseName = originalName.replace(/\[\d*\]$/, "");
          $(this).attr("name", baseName + "[" + w_counter + "]");
        }
      });

      // Update other input types
      clone_w
        .find(
          "input[type='text'], input[type='range'], input[type='checkbox'], textarea, select"
        )
        .each(function () {
          const originalName = $(this).attr("name");
          if (originalName) {
            const baseName = originalName.replace(/\[\d*\]$/, "");
            $(this).attr("name", baseName + "[" + w_counter + "]");
          }

          // Update range input IDs
          if ($(this).attr("type") === "range") {
            const w_oldId = $(this).attr("id");
            if (w_oldId) {
              $(this).attr("id", w_oldId + "_" + w_counter);
            }
          }
        });

      // Clear input values
      // clone_w.find("input[type='text'], textarea").val("");
      // clone_w.find("input[type='checkbox']").prop("checked", false);
      clone_w.find(".location-search-input").each(function () {
        enableAutocompleteForInput(this);
      });
      clone_w.appendTo(".appendwidgetboxcontainer");
      const newDropArea = clone_w.find(".drop-area")[0];
      if (targetId == 'uploadpdffield') {
        const pdfDropArea = clone_w.find(".drop-area-pdf")[0];
        if (pdfDropArea) {
          initPDFDropArea(pdfDropArea);
        }
      }
      if (targetId == 'imggalleryfield') {
        const imageDropArea = clone_w.find(".drop-area-gallary")[0];
        if (imageDropArea) {
          initImageGalleryUpload(imageDropArea);
        }
      }
      if (targetId == 'businesshoursfield') {
        initTimezoneSelection(clone_w);
      }

      if (newDropArea) {
        initDropArea(newDropArea);
      }
      create_preview(targetId + "_" + w_counter);
    }
  });

  // WordPress Media Uploader for Cover Image
  let coverFrame;
  $(document).on("click", "#coverUploadBtn", function (e) {
    e.preventDefault();

    if (coverFrame) {
      coverFrame.open();
      return;
    }

    coverFrame = wp.media({
      title: "Select or Upload Cover Image",
      button: { text: "Use this image" },
      multiple: false,
    });

    coverFrame.on("select", function () {
      const attachment = coverFrame.state().get("selection").first().toJSON();
      $("#coverPreviewImg").attr("src", attachment.url);
      $(".lp-profile-cover").val(attachment.url);
      $(".upload-placeholder").hide();
      $(".cover-preview").show();
    });

    coverFrame.open();
  });

  // Remove cover image
  $(document).on("click", "#coverRemoveBtn", function () {
    $("#coverPreviewImg").attr("src", "");
    $(".lp-profile-cover").val("");
    $(".cover-preview").hide();
    $(".upload-placeholder").show();
  });

  // WordPress Media Uploader for Profile Image
  let file_frame;
  $(".profileimgdiv").on("click", function (e) {
    if ($(e.target).closest("#thumbnailPopup").length) {
      return;
    }
    $("#thumbnailPopup").show();
  });

  $("#profilepopupchange").on("click", function (e) {
    e.preventDefault();

    if (file_frame) {
      file_frame.open();
      return;
    }

    file_frame = wp.media({
      title: "Select or Upload Thumbnail",
      button: { text: "Use this image" },
      multiple: false,
    });

    file_frame.on("select", function () {
      const attachment = file_frame.state().get("selection").first().toJSON();
      $(".lp-profile-image").val(attachment.url);
      $(".lp-thumbnail-preview").attr("src", attachment.url);
      $(".lp-thumbnail-preview").attr("src", attachment.url);
      var thumbnailPreview = document.querySelector('.linkpage-header img.img-box');
      thumbnailPreview.src = attachment.url;
      $(".lp-thumbnail-preview-popup").attr("src", attachment.url);
    });

    file_frame.open();
  });

  $("#profilepopupremove").on("click", function (e) {
    e.preventDefault();
    $(".lp-profile-image").val("");
    $(".lp-thumbnail-preview").attr(
      "src",
      $(".lp-thumbnail-preview").attr("data-default-url")
    );
    $(".lp-thumbnail-preview-popup").attr(
      "src",
      $(".lp-thumbnail-preview-popup").attr("data-default-url")
    );
    var thumbnailPreview = document.querySelector('.linkpage-header img.img-box');
    thumbnailPreview.src = $(".lp-thumbnail-preview-popup").attr("data-default-url");
  });

  $("#profilepopupclose").on("click", function (e) {
    e.preventDefault();
    $("#thumbnailPopup").hide();
  });

  /* DATA TABLE */
  $(document).on("click", ".actions .more-actions", function (e) {
    e.stopPropagation();
    const $actions = $(this).closest(".actions");
    let $menu = $actions.find(".actions-menu");

    $(".actions .actions-menu").not($menu).removeClass("active");
    $menu.toggleClass("active");
  });

  $(document).on("click", function () {
    $(".actions .actions-menu").removeClass("active");
  });

  if ($(".link-pages-listing-container #linkpage-table").length) {
    $(".link-pages-listing-container #linkpage-table").DataTable({
      pageLength: 10,
      lengthChange: false,
      ordering: true,
       searching: false,
      info: true,
      columnDefs: [
      { orderable: false, targets: 3 },
      { orderSequence: ["desc", "asc"], targets: 1 }
      ],
      order: [[1, "desc"]]
    });
  }

  /* POPUP HANDLERS */
  $("#create_linkpage").on("click", function () {

    //$(".link-pages-main-container #field1").hide();
    // $(".link-pages-main-container #field2").show();
  });

  const $optionSubmit = $(".cust-popup-content #optionsubmit");

  if ($optionSubmit.length) {
    
    $optionSubmit.on("click", function () {

      const $field2 = $(".link-pages-main-container #field2");
      const $field3 = $(".link-pages-main-container #field3");
      const $profileimgdiv = $("#profileimgdiv");
      const $uploaddivforoption2 = $("#uploaddivforoption2");
      $field2.hide();
      $field3.hide();
      $profileimgdiv.hide();
      $uploaddivforoption2.hide().removeClass("active");

      if ($(".cust-popup-content #profile").is(":checked")) {
        $field3.show();
        $profileimgdiv.show();
      } else if ($(".cust-popup-content #banner").is(":checked")) {
        $field3.show();
        $uploaddivforoption2.show();
        $profileimgdiv.show();
      } else if ($(".cust-popup-content #cover").is(":checked")) {
        $field3.show();
        $uploaddivforoption2.show().addClass("active");
      } else if ($(".cust-popup-content #minimal").is(":checked")) {
        $field3.show();
      }

      $(".cust_profile_layout#popup").hide();
    });
  }

  $(".close-popup-btn").on("click", function (e) {
    e.preventDefault();
    $("#popup").hide();
  });

  /* BUSINESS HOURS */
  // $(document).on(
  //   "change",
  //   '.business-hours .openclose input[type="checkbox"]',
  //   function () {
  //     const label = $(this).closest(".switchbox").find(".label-text");
  //     label.text($(this).is(":checked") ? "Open" : "Close");
  //   }
  // );
  $(document).on(
    "change",
    '.business-hours .openclose input[type="checkbox"]',
    function () {
      const $checkbox = $(this);
      const $switchbox = $checkbox.closest(".switchbox");
      const $timezoneCheckbox = $checkbox.closest(".timezoecheckbox");
      const label = $switchbox.find(".label-text");
      const isChecked = $checkbox.is(":checked");

      // Update label text
      label.text(isChecked ? "Open" : "Close");

      // Enable/disable time input fields
      const $startInput = $timezoneCheckbox.find(".timestartinput");
      const $endInput = $timezoneCheckbox.find(".timeendinput");

      if (isChecked) {
        $startInput.prop("disabled", false);
        $endInput.prop("disabled", false);
      } else {
        $startInput.prop("disabled", true);
        $endInput.prop("disabled", true);
      }
    }
  );

  /* SOCIAL LINKS */
  $(".socialicons-subcontainer").each(function () {
    enableSortable($(this));
  });

  $(document).on("click", ".forsocialbtns a.socialimg", function () {
    const $clickedA = $(this);
    const $clickedImg = $clickedA.find("img.socialimg").first();
    const imgAlt = $clickedImg.attr("alt") || "";

    // If plus sign clicked, open modal and do not add social menu
    if (imgAlt.trim().toLowerCase() === "plus") {
      // Replace this with your modal logic
      //   $("#popup").show(); // Example: show modal with id 'popup'
      alert("add new popup");
      return;
    }

    // ...existing code for adding social menu...
    const $section = $clickedA.closest(".forsocialbtndiv");
    const $widgetboxId = $clickedA.closest(".widgetbox").attr('id');
    console.log("$widgetboxId:", $widgetboxId);
    const $socialContainer = $section.find(".socialicons-subcontainer");
    const $socialPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .social-liks[data-preview-for="${$widgetboxId}"] .social-icon-box`
    );

    const imgSrc = $clickedImg.attr("src");

    // Social platform URLs
    const socialUrls = {
      facebook: "https://www.facebook.com/username",
      instagram: "https://www.instagram.com/username",
      twitter: "https://www.twitter.com/username",
      linkedin: "https://www.linkedin.com/in/username",
      "tik-tok": "https://www.tiktok.com/@username",
      snapchat: "https://www.snapchat.com/add/username",
      whatsapp: "https://wa.me/username",
      telegram: "https://t.me/username",
      vimeo: "https://vimeo.com/username",
      youtube: "https://www.youtube.com/@username",
      wistia: "https://wistia.com/username",
      twitch: "https://www.twitch.tv/username",
      discord: "https://discord.com/users/username",
      pinterest: "https://www.pinterest.com/username",
      yelp: "https://www.yelp.com/user_details?userid=username",
      paypal: "https://www.paypal.me/username",
      venmo: "https://venmo.com/username",
      cashapp: "https://cash.app/$username",
      calendly: "https://calendly.com/username",
      shopify: "https://username.myshopify.com",
      github: "https://github.com/username",
      dribbble: "https://dribbble.com/username",
      behance: "https://www.behance.net/username",
      plus: "",
    };

    let platform = imgAlt.trim().toLowerCase();
    let url = socialUrls[platform] || "";
    let title = imgAlt.charAt(0).toUpperCase() + imgAlt.slice(1);

    // Add to panel
    const $row = $(`
        <div class="um_contenter-inner-row cust-mt socialicon-subdiv" data-social="${platform}">
            <div class="cust-col-1">
                <div class="linkicons movewidgetforsocial">
                    <i class="fa-solid fa-up-down-left-right"></i>
                </div>
                <img src="${imgSrc}" alt="${imgAlt}" class="socialimg">
            </div>
            <div class="cust-col-max">
                <div class="togglewhitebox">
                    <div class="fortitle">
                        <input type="text" oninput="updateSocialLinkTitle(this)" placeholder="Enter title" value="${title}">
                    </div>
                    <div class="forurl">
                        <input type="text" placeholder="Enter URL" value="${url}">
                    </div>
                </div>
            </div>
            <div class="cust-col-1-1">
                <div class="linkicons deleteboxforsocial">
                    <i class="fa-solid fa-trash" style="color:#fa0000;"></i>
                </div>
            </div>
        </div>
    `);
    $socialContainer.append($row);

    // Add to mobile preview
    if ($socialPreview.length) {
      const $widget = $clickedA.closest('.widgetbox');
      const viewType = $widget.find('input[name^="social-view-type"]:checked').val() || 'icons';
      console.log("viewType:", viewType);
      let $previewRow = '';
      if (viewType === "icons") {
        $previewRow = $(
          `<a href="${url}" data-social="${platform}"><img src="${imgSrc}" alt="${imgAlt}" class="socialimg"></a>`
        );
      } else {
        $previewRow = $(
          `<a class="btn rounded-filled" style="position: relative;
              white-space: nowrap;
              overflow: hidden;
              text-overflow: ellipsis;
              border: none;
              color: #ffffff;
              font-size: 14px;
              width: 100%;
              margin-bottom: 8px;
              " href="${url}" data-social="${platform}">
                    <img src="${imgSrc}" alt="${imgAlt}" class="socialimg" style="width:20px;height:20px;margin-right:8px;">
                    ${platform}
                </a>`
        );
      }
      console.log("$previewRow:", $previewRow);
      $socialPreview.append($previewRow);
    }

    // Hide icon in Add Social Links
    $clickedA.hide();

    enableSortable($socialContainer);
  });

  $(document).on("click", ".deleteboxforsocial", function () {
    alert("Are you sure you want to delete this social link?");
    const $row = $(this).closest(".socialicon-subdiv");
    // Use .attr() to get alt value reliably
    const $img = $row.find("img.socialimg");
    const alt = $img.length ? $img.attr("alt") : "";
    const platform = alt.trim().toLowerCase();
    const widgetId = $(this).closest(".widgetbox").attr("id");

    // Show icon in Add Social Links
    $(".forsocialbtns a.socialimg")
      .filter(function () {
        return $(this).find("img.socialimg").attr("alt") === alt;
      })
      .show();

    // Debug: show alt value in console
    console.log("alt:", alt);

    // Remove from mobile preview (robust: match by img alt directly)
    $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .social-liks[data-preview-for="${widgetId}"] .social-icon-box a img.socialimg`
    ).each(function () {
      if ($(this).attr("alt") === alt) {
        $(this).closest("a").remove();
      }
    });

    // Remove from panel
    $row.remove();
  });

  function enableSortable($container) {
    if ($container.length && $container[0]) {
      Sortable.create($container[0], {
        animation: 150,
        handle: ".movewidgetforsocial",
        ghostClass: "dragging",
      });
    }
  }


  /* STEP NAVIGATION */
  $(".firstbtnrow .tablinks").on("click", function (event) {
    var cityName = $(this).data("step");
    change_step(event, cityName);
  });

  $(".um-btn-wraper.um-back-btn").on("click", function () {
    var data_step = $(this).attr("data-step");
    $('.firstbtnrow .tablinks[data-step="' + data_step + '"]').trigger("click");
  });

  $(".um-btn-wraper.um-next-btn").on("click", function () {
    var data_step = $(this).attr("data-step");
    $('.firstbtnrow .tablinks[data-step="' + data_step + '"]').trigger("click");
  });

  function change_step(evt, cityName) {
    $(".tabcontent").hide();
    $(".tablinks").removeClass("active");
    $("#" + cityName).show();
    $(evt.currentTarget).addClass("active");
  }

  $(".firstbtnrow .tablinks#defaultOpen").trigger("click");

  // Radio button handler for upload visibility
  $(document).on(
    "click",
    ".widgetbox .radio-group .radio-wrapper input",
    function () {
      const value = $(this).val();
      const $uploadBox = $(this)
        .closest(".radio-wrapper-main")
        .next(".cust-upload-box");

      if (value === "Thumbnail" || value === "Icons") {
        $uploadBox.addClass("active");
      } else {
        $uploadBox.removeClass("active");
      }
    }
  );

  $(document).on("change", "input[name^='social-view-type']", function () {
    const viewType = $(this).val();
    const widgetId = $(this).closest(".widgetbox").attr("id");
    console.log("Changing social view type to:", viewType, "for widget:", widgetId);
    const $socialPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .social-liks[data-preview-for="${widgetId}"] .social-icon-box`
    );
    const $socialContainer = $(this).closest('.widgetbox').find(".socialicons-subcontainer");
    console.log("$socialContainer:", $socialContainer);
    $socialPreview.empty();

    $socialContainer.find(".socialicon-subdiv").each(function () {
      const $img = $(this).find("img.socialimg");
      const alt = $img.attr("alt");
      const src = $img.attr("src");
      const title =
        $(this).find("input[placeholder='Enter title']").val() || alt;
      const url = $(this).find("input[placeholder='Enter URL']").val() || "#";

      if (viewType === "icons") {
        $socialPreview.append(
          `<a href="${url}" data-social="${alt}"><img src="${src}" alt="${alt}" class="socialimg"></a>`
        );
      } else if (viewType === "buttons") {
        $socialPreview.append(
          `<a class="btn rounded-filled" style="position: relative;
              white-space: nowrap;
              overflow: hidden;
              text-overflow: ellipsis;
              border: none;
              color: #ffffff;
              font-size: 14px;
              width: 100%;
              margin-bottom: 8px;
              " href="${url}" data-social="${alt}">
                    <img src="${src}" alt="${alt}" class="socialimg" style="width:20px;height:20px;margin-right:8px;">
                    ${title}
                </a>`
        );
      }
    });
  });

  // // When adding/removing social links, trigger view update
  // function updateSocialPreview() {
  //   $("input[name='social-view-type']:checked").trigger("change");
  // }

  // $(document).on("click", ".forsocialbtns a.socialimg", function () {
  //   // ...existing code for adding socialicon-subdiv...
  //   updateSocialPreview();
  // });

  // $(document).on("click", ".deleteboxforsocial", function () {
  //   // ...existing code for removing socialicon-subdiv...
  //   updateSocialPreview();
  // });
});

/* VANILLA JS FUNCTIONS */

// Color picker sync
document.addEventListener("DOMContentLoaded", function () {
  const pairs = document.querySelectorAll(".color-pair");

  pairs.forEach((pair) => {
    const hexInput = pair.querySelector(".hexcolor");
    const colorInput = pair.querySelector(".colorpicker");

    if (!hexInput || !colorInput) return;

    colorInput.addEventListener("input", function () {
      hexInput.value = this.value.toUpperCase();
    });

    hexInput.addEventListener("input", function () {
      let val = this.value.trim();
      if (val[0] !== "#") val = "#" + val;

      if (/^#[0-9A-Fa-f]{3}$/.test(val)) {
        val = "#" + val[1] + val[1] + val[2] + val[2] + val[3] + val[3];
      }

      if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
        colorInput.value = val;
      }
    });
  });

  // Profile popup handlers
  const profileDiv = document.getElementById("profileimgdiv");
  const popup = document.getElementById("thumbnailPopup");

  if (profileDiv && popup) {
    const closeBtn = popup.querySelector(".close-btn");

    profileDiv.addEventListener("click", function (e) {
      if (!e.target.closest("#thumbnailPopup")) {
        popup.style.display = "flex";
      }
    });

    if (closeBtn) {
      closeBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        popup.style.display = "none";
      });
    }

    popup.addEventListener("click", function (e) {
      if (e.target === popup) {
        popup.style.display = "none";
      }
    });
  }
});

// Utility functions
window.show = function (id) {
  const el = document.getElementById(id);
  if (el) el.style.display = "block";
};

window.hide = function (id) {
  const el = document.getElementById(id);
  if (el) el.style.display = "none";
};

// Drag and drop for file upload
document.addEventListener("DOMContentLoaded", function () {




});

// Toggle boxes
document.addEventListener("DOMContentLoaded", function () {
  const titles = document.querySelectorAll(".maincontentbox .boxtitle");

  titles.forEach((title) => {
    title.addEventListener("click", function () {
      const toggleBox =
        this.closest(".maincontentbox").querySelector(".togglebox");
      const icon = this.querySelector("i");

      if (toggleBox) {
        toggleBox.classList.toggle("active");
      }

      if (icon) {
        icon.classList.toggle("fa-angle-down");
        icon.classList.toggle("fa-angle-up");
      }
    });
  });
});

// Title and description preview update
document.addEventListener("DOMContentLoaded", function () {
  const wrappers = document.querySelectorAll('[data-id^="inputtextbox"]');

  wrappers.forEach((wrapper) => {
    const dataId = wrapper.getAttribute("data-id");
    const title = document.getElementById(`${dataId}Title`);
    if (!title) return;

    const input = wrapper.querySelector("input[type='text']:not(.hexcolor)");
    if (input) {
      input.addEventListener("input", () => {
        title.textContent = input.value || "Default Title";
      });
    }

    const fontSelect = wrapper.querySelector("select[name='lp-title-text-font']");
    if (fontSelect) {
      fontSelect.addEventListener("change", () => {
        switch (fontSelect.value) {
          case "h1":
            title.style.fontSize = "32px";
            title.style.fontWeight = "bold";
            break;
          case "h2":
            title.style.fontSize = "24px";
            title.style.fontWeight = "bold";
            break;
          case "h3":
            title.style.fontSize = "18px";
            title.style.fontWeight = "bold";
            break;
          default:
            title.style.fontSize = "24px";
            title.style.fontWeight = "normal";
        }
      });
    }
    const descriptionfontSelect = wrapper.querySelector("select[name='lp-desc-text-font']");
    if (descriptionfontSelect) {
      descriptionfontSelect.addEventListener("change", () => {
        switch (descriptionfontSelect.value) {
          case "h1":
            title.style.fontSize = "32px";
            title.style.fontWeight = "bold";
            break;
          case "h2":
            title.style.fontSize = "24px";
            title.style.fontWeight = "bold";
            break;
          case "h3":
            title.style.fontSize = "18px";
            title.style.fontWeight = "bold";
            break;
          default:
            title.style.fontSize = "15px";
            title.style.fontWeight = "normal";
        }
      });
    }

    const hexInput = wrapper.querySelector(".hexcolor");
    const colorPicker = wrapper.querySelector(".colorpicker");

    function updateColor(color) {
      if (!color) return;
      title.style.color = color;
      if (hexInput) hexInput.value = color;
      if (colorPicker) colorPicker.value = color;
    }
    if (hexInput)
      hexInput.addEventListener("input", () => updateColor(hexInput.value));
    if (colorPicker)
      colorPicker.addEventListener("input", () =>
        updateColor(colorPicker.value)
      );

    // --- alignment buttons (per-wrapper) ---
    const alignmentBtns = Array.from(
      wrapper.querySelectorAll(".font-alignment")
    );
    alignmentBtns.forEach((btn) => {
      const icon = btn.querySelector("i");
      // detect alignment from icon class
      let align = "left";
      if (icon?.classList.contains("fa-align-center")) align = "center";
      else if (icon?.classList.contains("fa-align-right")) align = "right";

      btn.addEventListener("click", () => {
        // set alignment and active class only inside this wrapper
        title.style.textAlign = align;
        alignmentBtns.forEach((b) => b.classList.remove("active"));
        btn.classList.add("active");
      });
    });

    // --- font style buttons (detect by icon class) ---
    const styleBtns = Array.from(wrapper.querySelectorAll(".fontstyle"));
    let boldBtn = null,
      italicBtn = null,
      underlineBtn = null,
      strikeBtn = null;

    styleBtns.forEach((btn) => {
      const icon = btn.querySelector("i");
      if (!icon) return;
      if (icon.classList.contains("fa-bold")) boldBtn = btn;
      else if (icon.classList.contains("fa-italic")) italicBtn = btn;
      else if (icon.classList.contains("fa-underline")) underlineBtn = btn;
      else if (icon.classList.contains("fa-strikethrough")) strikeBtn = btn;
    });

    // helper to compose textDecoration from active states
    function updateTextDecoration() {
      const parts = [];
      if (underlineBtn?.classList.contains("active")) parts.push("underline");
      if (strikeBtn?.classList.contains("active")) parts.push("line-through");
      title.style.textDecoration = parts.length ? parts.join(" ") : "none";
    }

    if (boldBtn) {
      boldBtn.addEventListener("click", () => {
        boldBtn.classList.toggle("active");
        title.style.fontWeight = boldBtn.classList.contains("active")
          ? "bold"
          : "";
      });
    }

    if (italicBtn) {
      italicBtn.addEventListener("click", () => {
        italicBtn.classList.toggle("active");
        title.style.fontStyle = italicBtn.classList.contains("active")
          ? "italic"
          : "";
      });
    }

    if (underlineBtn) {
      underlineBtn.addEventListener("click", () => {
        underlineBtn.classList.toggle("active");
        updateTextDecoration();
      });
    }

    if (strikeBtn) {
      strikeBtn.addEventListener("click", () => {
        strikeBtn.classList.toggle("active");
        updateTextDecoration();
      });
    }

    // Optional: initialize button states from existing inline styles (if you want)
    // e.g. if (window.getComputedStyle(title).fontWeight === "700") boldBtn?.classList.add("active");
  });
});

const widgetsBtn = document.getElementById("widgetsBtn");
const inputType = document.getElementById("input-type");

// toggle menu on button click
widgetsBtn.addEventListener("click", function (e) {
  e.stopPropagation();
  inputType.classList.toggle("show");
});

// handle option click
document.querySelectorAll(".input-type .selectitems").forEach((item) => {
  item.addEventListener("click", function (e) {
    e.stopPropagation();
    // alert("You selected: " + this.textContent.trim());
    inputType.classList.remove("show"); // close after selection
  });
});

// close when clicking outside
document.addEventListener("click", function () {
  inputType.classList.remove("show");
});

jQuery(document).ready(function ($) {

  // ========================================
  // EDIT MODE HANDLER
  // ========================================
  const urlParams = new URLSearchParams(window.location.search);
  const editId = urlParams.get('edit_id');

  let activeInput = null;
  let amPm = "AM";

  function timepicker() {
    function createCircleOfDivs(
      num,
      radius,
      offsetX,
      offsetY,
      className,
      add,
      step
    ) {
      var x, y;
      for (var n = 0; n < num; n++) {
        x = radius * Math.cos((n / num) * 2 * Math.PI);
        y = radius * Math.sin((n / num) * 2 * Math.PI);
        var div = document.createElement("div");
        div.className = className;

        // Fixed: Hour circle ordered like a real clock (12 at top, 3 right, etc.)
        if (className.startsWith("hour")) {
          // let hour = (n + 3) % 12 + 1; // Rotated to position 12 at top
          let hour = ((n + 2) % 12) + 1; // Rotates correctly (12 on top, 3 right, 6 bottom, etc.)
          div.textContent = hour;
        } else {
          // Minute circle
          if (n % step === 0) {
            let val = n + 15 >= 60 ? n + 15 - 60 : n + 15;
            div.setAttribute("data-value", val);
            div.textContent = val;
          } else {
            let val = n + 15 >= 60 ? n + 15 - 60 : n + 15;
            div.setAttribute("data-value", val);
            div.textContent = "·";
          }
        }

        div.style.left = x + offsetX + "px";
        div.style.top = y + offsetY + "px";
        $(".timepicker .circle")[0].appendChild(div);
      }
    }
    const now = new Date();

    let hours = now.getHours();

    let minutes = now.getMinutes();
    // Convert 24h to 12h
    amPm = "PM";
    if (hours >= 12) {
      hours = hours > 12 ? hours - 12 : hours;
      amPm = "AM";
    } else {
      hours = hours === 0 ? 12 : hours;
    }

    $(".timepicker .top .h").text(hours.toString().padStart(2, "0"));
    $(".timepicker .top .m").text(minutes.toString().padStart(2, "0"));
    $(".timepicker .top .ampm").text(amPm);

    function selectHours() {
      $(".timepicker .circle").html("");
      createCircleOfDivs(12, 90, 105, 105, "hour", 0, 1); // inner 1-12
      $(".timepicker .circle").append('<div class="mid"></div>');

      $(".timepicker .top .active").removeClass("active");
      $(".timepicker .top .h").addClass("active");

      $(".hour")
        .off("mouseup")
        .on("mouseup", function () {
          $(".timepicker .top .h").text($(this).text().padStart(2, "0"));
          selectMinutes();
        });
    }

    function selectMinutes() {
      $(".timepicker .circle").html("");
      $(".timepicker .top .active").removeClass("active");
      $(".timepicker .top .m").addClass("active");

      // Minutes: keep circular layout
      createCircleOfDivs(60, 101, 115, 115, "min", 0, 5);

      $(".min")
        .off("mouseup")
        .on("mouseup", function () {
          $(".timepicker .top .m").text(
            $(this).attr("data-value").padStart(2, "0")
          );
        });
    }

    selectHours();

    $(".timepicker .top span.h").off("click").on("click", selectHours);
    $(".timepicker .top span.m").off("click").on("click", selectMinutes);

    $(".timepicker .top span.ampm")
      .off("click")
      .on("click", function () {
        amPm = amPm === "AM" ? "PM" : "AM";
        $(this).text(amPm);
      });
  }

  // $(".custom-timepicker-popup").hide();

  // Show popup
  $(document).on("click", ".timestartinput, .timeendinput", function (e) {
    e.stopPropagation();
    activeInput = this;

    const offset = $(this).offset();
    const height = $(this).outerHeight();

    $(".custom-timepicker-popup")
      .css({
        top: offset.top + height + 8,
        left: offset.left,
      })
      .show();

    timepicker();
  });

  // OK button
  $(document).on("click", ".timepicker .action.ok", function () {
    const h = $(".timepicker .top .h").text();
    const m = $(".timepicker .top .m").text();
    const t = amPm;
    `       `;

    const selectedTime = h + ":" + m + " " + t;

    if ($(activeInput).hasClass("timeendinput")) {
      const startTime = $(".timestartinput").val();
      if (startTime) {
        const [sh, sm, speriod] = startTime
          .match(/(\d+):(\d+) (AM|PM)/)
          .slice(1);
        const [eh, em] = [h, m];
        const startMin = ((+sh % 12) + (speriod === "PM" ? 12 : 0)) * 60 + +sm;
        const endMin = ((+eh % 12) + (t === "PM" ? 12 : 0)) * 60 + +em;

        if (endMin < startMin) {
          alert("End time cannot be before start time");
          return;
        }
      }
    }

    if (activeInput) $(activeInput).val(selectedTime).trigger("change");
    $(".custom-timepicker-popup").hide();
  });

  // Cancel
  $(document).on("click", ".timepicker .action.cancel", function () {
    $(".custom-timepicker-popup").hide();
  });

  // Hide popup on outside click
  $(document).on("click", function (e) {
    if (
      !$(e.target).closest(".timepicker, .timestartinput, .timeendinput").length
    ) {
      $(".custom-timepicker-popup").hide();
    }
  });
  // Add this to your link-pages.js file

  // Ensure jQuery UI Sortable is loaded for this to work.

  $(".appendwidgetboxcontainer").sortable({
    // Use the .movewidget element as the handle to restrict where the user can drag from
    handle: ".movewidget",

    // Restrict dragging to the vertical axis
    axis: "y",

    // The 'stop' event fires when the user releases the mouse button after dragging, 
    // and the new order is established in the form.
    stop: function (event, ui) {

      // Get the preview container where the elements need to be reordered
      const $previewContainer = $(".mobilelayoutmain #linkpage-id .linkpage-body");

      // Iterate through all widget elements in their *new* sorted order
      $(".appendwidgetboxcontainer .widgetbox").each(function () {

        // Get the unique ID of the currently iterated widget (e.g., 'buttontextfields_1')
        const widgetId = $(this).attr("id");

        // Find the corresponding preview element in the linkpage-body using its data-preview-for attribute
        const $previewElement = $previewContainer.find(`[data-preview-for="${widgetId}"]`);

        // If the preview element is found, re-append it to the preview container.
        // jQuery's .append() function automatically removes the element from its old position 
        // before adding it to the end of the new container, effectively re-ordering it.
        if ($previewElement.length) {
          $previewContainer.append($previewElement);
        }
      });

      // Optional: Trigger a window resize or similar event if any preview widgets 
      // (like maps, galleries, etc.) need to redraw after a reorder.
      // $(window).trigger('resize');
    }
  });

  // ============================================================================
  // AJAX FORM SUBMISSION HANDLER FOR LINK PAGES
  // ============================================================================

  /**
   * Handle form submission when FINISH button is clicked
   */
  // $(document).on('click', 'button[name="link-pages-submit"]', function (e) {
  //   e.preventDefault();

  //   // Validate Step 3 before submission
  //   // if (!validateStep('Step3')) {
  //   //   alert('Please fill in all required fields in Step 3.');
  //   //   return;
  //   // }

  //   // Show loading state
  //   const $submitBtn = $(this);
  //   const originalText = $submitBtn.text();
  //   $submitBtn.prop('disabled', true).text('Submitting...');

  //   // Prepare form data
  //   prepareLinkPageData()
  //     .then(function (formData) {
  //       // Submit via AJAX
  //       return submitLinkPageForm(formData);
  //     })
  //     .then(function (response) {
  //       // Handle success
  //       console.log('Link page created successfully:', response);
  //       alert('Link page created successfully!');

  //       // Optionally redirect or reset form
  //       if (response.data && response.data.post_id) {
  //         // Redirect to the created link page or back to list
  //         window.location.href = window.location.href;
  //       }
  //     })
  //     .catch(function (error) {
  //       // Handle error
  //       console.error('Error submitting link page:', error);
  //       alert('Error: ' + (error.message || 'Failed to create link page. Please try again.'));
  //     })
  //     .finally(function () {
  //       // Restore button state
  //       $submitBtn.prop('disabled', false).text(originalText);
  //     });
  // });

  /**
   * Prepare all link page data including common fields and widgets
   * @returns {Promise<FormData>}
   */
  function prepareLinkPageData() {
    return new Promise(function (resolve, reject) {
      try {
        const formData = new FormData();
        const commonData = {};
        const widgetsData = [];

        // Check for edit ID
        const urlParams = new URLSearchParams(window.location.search);
        const editId = urlParams.get('edit_id');
        if (editId) {
          commonData['post_id'] = editId;
        }

        // ========================================
        // STEP 1: Collect Common Fields (lp-* fields from all steps)
        // ========================================

        $('.link-pages-form').find('input, select, textarea').each(function () {
          const $field = $(this);
          const name = $field.attr('name');

          // Skip if no name or if it's a widget field
          if (!name || name.startsWith('wg-')) {
            return;
          }

          // Handle different input types
          if ($field.attr('type') === 'checkbox') {
            commonData[name] = $field.is(':checked') ? '1' : '0';
          } else if ($field.attr('type') === 'radio') {
            if ($field.is(':checked')) {
              commonData[name] = $field.val();
            }
          } else if ($field.attr('type') === 'file') {
            // Skip file inputs in common data - they'll be handled separately
            return;
          } else {
            const value = $field.val();
            if (value !== null && value !== undefined) {
              commonData[name] = value;
            }
          }
        });

        // Handle profile/layout images from Step 1
        const profileFileInput = document.getElementById('lp-w-profile-file');
        if (profileFileInput && profileFileInput.files && profileFileInput.files[0]) {
          formData.append('lp_profile_image', profileFileInput.files[0]);
          commonData['lp-profile-image-uploaded'] = '1';
        }

        const layoutFileInput = document.getElementById('lp-w-layout-file');
        if (layoutFileInput && layoutFileInput.files && layoutFileInput.files[0]) {
          formData.append('lp_layout_image', layoutFileInput.files[0]);
          commonData['lp-layout-image-uploaded'] = '1';
        }

        // Handle background image/video from Step 2
        const bgImageInput = document.querySelector('#drop-area-background-image .upload-input');
        if (bgImageInput && bgImageInput.files && bgImageInput.files[0]) {
          formData.append('lp_background_image', bgImageInput.files[0]);
          commonData['lp-background-image-uploaded'] = '1';
        }

        const bgVideoInput = document.querySelector('#drop-area-background-video .upload-input');
        if (bgVideoInput && bgVideoInput.files && bgVideoInput.files[0]) {
          formData.append('lp_background_video', bgVideoInput.files[0]);
          commonData['lp-background-video-uploaded'] = '1';
        }

        // ========================================
        // STEP 2: Collect Widget Data
        // ========================================

        $('.appendwidgetboxcontainer .widgetbox').each(function (index) {
          const $widget = $(this);
          const widgetId = $widget.attr('id');
          const widgetType = $widget.attr('data-widget-type');

          if (!widgetId || !widgetType) {
            return; // Skip if no ID or type
          }

          const widgetData = {
            id: widgetId,
            type: widgetType,
            order: index,
            fields: {}
          };

          // Collect all fields within this widget
          $widget.find('input, select, textarea').each(function () {
            const $field = $(this);
            const name = $field.attr('name');

            if (!name) return;

            // Remove any bracketed index or array markers from name for cleaner field names
            const fieldName = name.replace(/\[[^\]]*\]/g, '');

            // Handle different input types
            if ($field.attr('type') === 'checkbox') {
              widgetData.fields[fieldName] = $field.is(':checked') ? '1' : '0';
            } else if ($field.attr('type') === 'radio') {
              if ($field.is(':checked')) {
                widgetData.fields[fieldName] = $field.val();
              }
            } else if ($field.attr('type') === 'file') {
              // Handle file uploads for widgets
              const fileKey = $field.attr('data-file-key') || fieldName;

              if ($field[0].files && $field[0].files.length > 0) {
                // For single file
                if ($field[0].files.length === 1) {
                  formData.append(`widget_files[${widgetId}][${fileKey}]`, $field[0].files[0]);
                  widgetData.fields[fileKey + '_uploaded'] = '1';
                } else {
                  // For multiple files (like image gallery)
                  for (let i = 0; i < $field[0].files.length; i++) {
                    formData.append(`widget_files[${widgetId}][${fileKey}][]`, $field[0].files[i]);
                  }
                  widgetData.fields[fileKey + '_count'] = $field[0].files.length;
                }
              }
            } else {
              const value = $field.val();
              if (value !== null && value !== undefined && value !== '') {
                widgetData.fields[fieldName] = value;
              }
            }
          });

          // Special handling for image gallery widget
          if (widgetType === 'img-gallery') {
            const galleryArea = $widget.find('.drop-area-gallery')[0];
            if (galleryArea && typeof galleryArea.getUploadedImages === 'function') {
              const uploadedImages = galleryArea.getUploadedImages();
              if (uploadedImages && uploadedImages.length > 0) {
                uploadedImages.forEach(function (img, idx) {
                  if (img.file) {
                    formData.append(`widget_files[${widgetId}][gallery][]`, img.file);
                  }
                });
                widgetData.fields['gallery_image_count'] = uploadedImages.length;
              }
            }
          }

          // Special handling for social links widget
          if (widgetType === 'social') {
            const socialLinks = [];
            $widget.find('.social-link-item').each(function () {
              const platform = $(this).find('[name*="platform"]').val();
              const url = $(this).find('[name*="url"]').val();
              if (platform && url) {
                socialLinks.push({ platform: platform, url: url });
              }
            });
            if (socialLinks.length > 0) {
              widgetData.fields['social_links'] = socialLinks;
            }
          }

          // Special handling for business hours widget
          if (widgetType === 'business-hours') {
            const businessHours = {};
            $widget.find('.day-hours-row').each(function () {
              const day = $(this).attr('data-day');
              const isOpen = $(this).find('[name*="is-open"]').is(':checked');
              const startTime = $(this).find('.timestartinput').val();
              const endTime = $(this).find('.timeendinput').val();

              if (day) {
                businessHours[day] = {
                  is_open: isOpen ? '1' : '0',
                  start_time: startTime || '',
                  end_time: endTime || ''
                };
              }
            });
            if (Object.keys(businessHours).length > 0) {
              widgetData.fields['business_hours'] = businessHours;
            }
          }

          widgetsData.push(widgetData);
        });

        // ========================================
        // STEP 3: Build Final JSON Structure
        // ========================================

        const finalData = {
          common: commonData,
          widgets: widgetsData
        };

        // Add JSON data to FormData
        formData.append('linkpage_data', JSON.stringify(finalData));

        // Add WordPress AJAX action
        formData.append('action', 'save_link_page');

        // Add nonce for security (if available)
        if (typeof LinkPages !== 'undefined' && LinkPages.nonce) {
          formData.append('nonce', LinkPages.nonce);
        }

        console.log('Prepared Link Page Data:', finalData);
        console.log('Total Widgets:', widgetsData.length);

        resolve(formData);

      } catch (error) {
        reject(error);
      }
    });
  }

  /**
   * Submit the form data via AJAX
   * @param {FormData} formData
   * @returns {Promise}
   */
  function submitLinkPageForm(formData) {
    return new Promise(function (resolve, reject) {
      $.ajax({
        url: qrAjax.ajax_url, // WordPress AJAX URL
        type: 'POST',
        data: formData,
        processData: false,  // Don't process the data
        contentType: false,  // Don't set content type (FormData sets it)
        dataType: 'json',
        success: function (response) {
          if (response.success) {
            resolve(response);
          } else {
            reject(new Error(response.data && response.data.message ? response.data.message : 'Unknown error occurred'));
          }
        },
        error: function (xhr, status, error) {
          console.error('AJAX Error:', { xhr, status, error });
          reject(new Error('Network error: ' + error));
        }
      });
    });
  }

});

jQuery(function ($) {

  // ============================================================================
  // UPDATE FORM DATA PREPARATION TO INCLUDE LAYOUT
  // ============================================================================

  /**
   * Prepare all link page data including layout
   * Note: Add this to your existing prepareLinkPageData function
   */
  function enhancedPrepareLinkPageData() {
    return new Promise(function (resolve, reject) {
      try {
        const formData = new FormData();
        const commonData = {};
        const widgetsData = [];

        // Check for edit ID
        const urlParams = new URLSearchParams(window.location.search);
        const editId = urlParams.get('edit_id');
        if (editId) {
          formData.append('edit_id', editId);
        }

        // ========================================
        // Collect Common Fields INCLUDING LAYOUT
        // ========================================

        // Get selected layout
        const selectedLayout = $('input[name="layout"]:checked').val();
        if (selectedLayout) {
          commonData['lp-profile-layout'] = selectedLayout;
        }

        // Get layout full-width setting
        const isFullWidth = $('input[name="lp-profile-cover-full-width"]').is(':checked');
        commonData['lp-profile-cover-full-width'] = isFullWidth ? '1' : '0';

        // Collect all other common fields
        $('.link-pages-form').find('input, select, textarea').each(function () {
          const $field = $(this);
          const name = $field.attr('name');

          if (!name || name.startsWith('wg-')) {
            return;
          }

          if ($field.attr('type') === 'checkbox') {
            commonData[name] = $field.is(':checked') ? '1' : '0';
          } else if ($field.attr('type') === 'radio') {
            if ($field.is(':checked')) {
              commonData[name] = $field.val();
            }
          } else if ($field.attr('type') === 'file') {
            return;
          } else {
            const value = $field.val();
            if (value !== null && value !== undefined) {
              commonData[name] = value;
            }
          }
        });

        // Handle file uploads
        const profileFileInput = document.getElementById('lp-w-profile-file');
        if (profileFileInput && profileFileInput.files && profileFileInput.files[0]) {
          formData.append('lp_profile_image', profileFileInput.files[0]);
          commonData['lp-profile-image-uploaded'] = '1';
        }

        const layoutFileInput = document.getElementById('lp-w-layout-file');
        if (layoutFileInput && layoutFileInput.files && layoutFileInput.files[0]) {
          formData.append('lp_layout_image', layoutFileInput.files[0]);
          commonData['lp-layout-image-uploaded'] = '1';
        }

        const bgImageInput = document.querySelector('#drop-area-background-image .upload-input');
        if (bgImageInput && bgImageInput.files && bgImageInput.files[0]) {
          formData.append('lp_background_image', bgImageInput.files[0]);
          commonData['lp-background-image-uploaded'] = '1';
        }

        const bgVideoInput = document.querySelector('#drop-area-background-video .upload-input');
        if (bgVideoInput && bgVideoInput.files && bgVideoInput.files[0]) {
          formData.append('lp_background_video', bgVideoInput.files[0]);
          commonData['lp-background-video-uploaded'] = '1';
        }

        // ========================================
        // Collect Widget Data
        // ========================================

        $('.appendwidgetboxcontainer .widgetbox').each(function (index) {
          const $widget = $(this);
          const widgetId = $widget.attr('id');
          const widgetType = $widget.attr('data-widget-type');

          if (!widgetId || !widgetType) {
            return;
          }

          const widgetData = {
            id: widgetId,
            type: widgetType,
            order: index,
            fields: {}
          };

          // Collect all fields within this widget
          $widget.find('input, select, textarea').each(function () {
            const $field = $(this);
            const name = $field.attr('name');

            if (!name) return;

            const fieldName = name.replace(/\[[^\]]*\]/g, '');

            if ($field.attr('type') === 'checkbox') {
              widgetData.fields[fieldName] = $field.is(':checked') ? '1' : '0';
            } else if ($field.attr('type') === 'radio') {
              if ($field.is(':checked')) {
                widgetData.fields[fieldName] = $field.val();
              }
            } else if ($field.attr('type') === 'file') {
              const fileKey = $field.attr('data-file-key') || fieldName;

              if ($field[0].files && $field[0].files.length > 0) {
                if ($field[0].files.length === 1) {
                  formData.append(`widget_files[${widgetId}][${fileKey}]`, $field[0].files[0]);
                  widgetData.fields[fileKey + '_uploaded'] = '1';
                } else {
                  for (let i = 0; i < $field[0].files.length; i++) {
                    formData.append(`widget_files[${widgetId}][${fileKey}][]`, $field[0].files[i]);
                  }
                  widgetData.fields[fileKey + '_count'] = $field[0].files.length;
                }
              }
            } else {
              const value = $field.val();
              if (value !== null && value !== undefined && value !== '') {
                widgetData.fields[fieldName] = value;
              }
            }
          });

          // Special handling for social links
          if (widgetType === 'social') {
            const socialLinks = [];
            $widget.find('.socialicon-subdiv').each(function () {
              const platform = $(this).attr('data-social');
              const title = $(this).find('input[placeholder="Enter title"]').val();
              const url = $(this).find('input[placeholder="Enter URL"]').val();
              if (platform && url) {
                socialLinks.push({ platform: platform, title: title, url: url });
              }
            });
            if (socialLinks.length > 0) {
              widgetData.fields['social_links'] = socialLinks;
            }
          }

          // Special handling for business hours
          if (widgetType === 'business-hours') {
            const businessHours = {};
            $widget.find('.timezoecheckbox').each(function () {
              const day = $(this).find('input[type="checkbox"]').val();
              const isOpen = $(this).find('input[type="checkbox"]').is(':checked');
              const startTime = $(this).find('.timestartinput').val();
              const endTime = $(this).find('.timeendinput').val();

              if (day) {
                businessHours[day] = {
                  is_open: isOpen ? '1' : '0',
                  start_time: startTime || '',
                  end_time: endTime || ''
                };
              }
            });
            if (Object.keys(businessHours).length > 0) {
              widgetData.fields['business_hours'] = businessHours;
            }
          }

          widgetsData.push(widgetData);
        });

        // Build final JSON structure
        const finalData = {
          common: commonData,
          widgets: widgetsData
        };

        formData.append('linkpage_data', JSON.stringify(finalData));
        formData.append('action', 'save_link_page');

        if (typeof LinkPages !== 'undefined' && LinkPages.nonce) {
          formData.append('nonce', LinkPages.nonce);
        }

        console.log('Prepared Link Page Data:', finalData);
        resolve(formData);

      } catch (error) {
        reject(error);
      }
    });
  }

  // ============================================================================
  // Initialize edit mode on document ready
  // ============================================================================
  // ============================================================================
  // Update form submission handler
  // ============================================================================

  $(document).on('click', 'button[name="link-pages-submit"]', function (e) {
    e.preventDefault();

    const $submitBtn = $(this);
    const originalText = $submitBtn.text();
    $submitBtn.prop('disabled', true).text('Submitting...');

    enhancedPrepareLinkPageData()
      .then(function (formData) {
        return $.ajax({
          url: qrAjax.ajax_url,
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          dataType: 'json'
        });
      })
      .then(function (response) {
        if (response.success) {
          console.log('Link page saved successfully:', response);
          alert(response.data.message || 'Link page saved successfully!');
          window.location.href = window.location.pathname + '?linkpage';
        } else {
          throw new Error(response.data.message || 'Unknown error occurred');
        }
      })
      .catch(function (error) {
        console.error('Error submitting link page:', error);
        alert('Error: ' + (error.message || 'Failed to save link page. Please try again.'));
      })
      .finally(function () {
        $submitBtn.prop('disabled', false).text(originalText);
      });
  });

  // Export functions for external use if needed

  // window.LinkPageEditMode = {
  //   init: initEditMode,
  //   loadCommonFields: loadCommonFields,
  //   loadWidgets: loadWidgets,
  //   prepareLinkPageData: enhancedPrepareLinkPageData
  // };

  window.updateTextSectionAlign = function (input) {
    const $input = $(input);
    const alignment = $input.val();
    const widgetId = $input.closest(".widgetbox").attr("id");
    const $textPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .text-box-preview[data-preview-for="${widgetId}"] p`
    );

    // Update text alignment in mobile preview
    $textPreview.css("text-align", alignment);

    // Update active state
    $input.closest('.text-alignment').find('.style-wrapper').removeClass('active');
    if ($input.is(':checked')) {
      $input.closest('.style-wrapper').addClass('active');
    }
  };

  window.updateTextSectionStyle = function (input, style) {
    const $input = $(input);
    const isChecked = $input.is(':checked');
    const widgetId = $input.closest(".widgetbox").attr("id");
    const $textPreview = $(
      `.mobilelayoutmain #linkpage-id .linkpage-body .text-box-preview[data-preview-for="${widgetId}"] p`
    );

    // Toggle text style in mobile preview
    switch (style) {
      case "bold":
        $textPreview.css("font-weight", isChecked ? "bold" : "normal");
        break;
      case "italic":
        $textPreview.css("font-style", isChecked ? "italic" : "normal");
        break;
      case "underline":
      case "strikethrough":
        let decorations = [];
        const $container = $input.closest('.font-styling');
        // Check both potential decoration inputs
        if ($container.find('input[name*="wg-w-text-style-underline"]').is(':checked')) decorations.push('underline');
        if ($container.find('input[name*="wg-w-text-style-strikethrough"]').is(':checked')) decorations.push('line-through');

        $textPreview.css("text-decoration", decorations.length ? decorations.join(" ") : "none");
        break;
      default:
        break;
    }

    if (isChecked) {
      $input.closest('.style-wrapper').addClass('active');
    } else {
      $input.closest('.style-wrapper').removeClass('active');
    }
  };

  /**
   * Title Section Updates
   */
  window.updateTitleTextSectionAlign = function (input) {
    const $input = $(input);
    const alignment = $input.val();
    const $preview = $('#inputtextboxTitle');

    // Update text alignment
    $preview.css("text-align", alignment);

    // Update active state
    $input.closest('.text-alignment').find('.style-wrapper').removeClass('active');
    if ($input.is(':checked')) {
      $input.closest('.style-wrapper').addClass('active');
    }
  };

  window.updateTitleTextSectionStyle = function (input, style) {
    const $input = $(input);
    const isChecked = $input.is(':checked');
    const $preview = $('#inputtextboxTitle');

    switch (style) {
      case "bold":
        $preview.css("font-weight", isChecked ? "bold" : "normal");
        break;
      case "italic":
        $preview.css("font-style", isChecked ? "italic" : "normal");
        break;
      case "underline":
      case "strikethrough":
        let decorations = [];
        const $container = $input.closest('.font-styling');
        if ($container.find('input[name="lp-title-text-style-underline"]').is(':checked')) decorations.push('underline');
        if ($container.find('input[name="lp-title-text-style-strikethrough"]').is(':checked')) decorations.push('line-through');

        $preview.css("text-decoration", decorations.length ? decorations.join(" ") : "none");
        break;
    }

    if (isChecked) {
      $input.closest('.style-wrapper').addClass('active');
    } else {
      $input.closest('.style-wrapper').removeClass('active');
    }
  };

  window.updateButtonTextSectionStyle = function (input, style) {
    const $input = $(input);
    const isChecked = $input.is(':checked');
    const $preview = $('.linkpage-body');

    switch (style) {
      case "bold":
        $preview.toggleClass("bold");
        break;
      case "italic":
        $preview.toggleClass("italic");
        break;
      case "underline":
        $preview.toggleClass("underline");
        break;
    }

    if (isChecked) {
      $input.closest('.style-wrapper').addClass('active');
    } else {
      $input.closest('.style-wrapper').removeClass('active');
    }
  };

  /**
   * Description Section Updates
   */
  window.updateDescTextSectionAlign = function (input) {
    const $input = $(input);
    const alignment = $input.val();
    const $preview = $('#inputtextbox2Title');

    // Update text alignment
    $preview.css("text-align", alignment);

    // Update active state
    $input.closest('.text-alignment').find('.style-wrapper').removeClass('active');
    if ($input.is(':checked')) {
      $input.closest('.style-wrapper').addClass('active');
    }
  };

  window.updateDescTextSectionStyle = function (input, style) {
    const $input = $(input);
    const isChecked = $input.is(':checked');
    const $preview = $('#inputtextbox2Title');

    switch (style) {
      case "bold":
        $preview.css("font-weight", isChecked ? "bold" : "normal");
        break;
      case "italic":
        $preview.css("font-style", isChecked ? "italic" : "normal");
        break;
      case "underline":
      case "strikethrough":
        let decorations = [];
        const $container = $input.closest('.font-styling');
        if ($container.find('input[name="lp-desc-text-style-underline"]').is(':checked')) decorations.push('underline');
        if ($container.find('input[name="lp-desc-text-style-strikethrough"]').is(':checked')) decorations.push('line-through');

        $preview.css("text-decoration", decorations.length ? decorations.join(" ") : "none");
        break;
    }

    if (isChecked) {
      $input.closest('.style-wrapper').addClass('active');
    } else {
      $input.closest('.style-wrapper').removeClass('active');
    }
  };

  
});