/* script.js */
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablink");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}

document.addEventListener("DOMContentLoaded", function() {
    document.querySelector(".tablink.active").click();
});


// Activate the first tab by default
document.getElementById("used").style.display = "block";
document.getElementsByClassName("tablink")[0].className += " active";

// JavaScript for confirming delete action
function confirmDelete() {
    return confirm('Are you sure you want to delete this image?');
}



// background-images-script.js
document.addEventListener("DOMContentLoaded", function() {
    // ... (your existing script)

    // Send backgroundImages data to the server using AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

    // Convert backgroundImages array to a JSON string
    var data = 'action=process_background_images&background_images=' + JSON.stringify(backgroundImages);

    xhr.send(data);
});

// Function to fetch async images and send them to server
function fetchAndSendAsyncImages() {
    const images = document.querySelectorAll('img');
    const asyncImages = [];

    images.forEach(img => {
        if (img.getAttribute('decoding') === 'async') {
            asyncImages.push(img.src);
        }
    });

    // Send asyncImages array to server via AJAX
    fetch('process_images.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ asyncImages }),
    })
    .then(response => response.json())
    .then(data => console.log(data))
    .catch(error => console.error('Error:', error));
}

jQuery(document).ready(function($) {
    function updateBulkActions() {
        var selectedCount = $('.image-checkbox:checked').length;
        if (selectedCount > 0) {
            $('.bulk-delete').show();
        } else {
            $('.bulk-delete').hide();
        }
    }

    $('.select-all').click(function() {
        var table = $(this).closest('table');
        var checkboxes = table.find('.image-checkbox');
        checkboxes.prop('checked', this.checked);
        updateBulkActions();
    });

    $(document).on('change', '.image-checkbox', function() {
        updateBulkActions();
    });

    // To ensure bulk actions are updated on page load if any checkbox is pre-checked
    updateBulkActions();
});
