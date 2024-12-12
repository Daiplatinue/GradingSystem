// Profile Image Preview
function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').src = e.target.result;
            document.querySelectorAll('.profile-image').forEach(img => {
                img.src = e.target.result;
            });
        }
        reader.readAsDataURL(file);
    }
}

// Modal Management
function openEditModal() {
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

// Form Submission
async function handleSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('../api/update-profile.php', {
            method: 'POST',
            body: formData
        });
        
        const responseText = await response.text();
        
        // Try to parse as JSON first
        try {
            const data = JSON.parse(responseText);
            if (data.success) {
                if (data.image_path) {
                    // Update all profile images on the page
                    document.querySelectorAll('img[src*="uploads/profiles"]').forEach(img => {
                        img.src = data.image_path;
                    });
                }
                closeEditModal();
                window.location.reload();
            } else {
                alert(data.message || 'Failed to update profile. Please try again.');
            }
        } catch (jsonError) {
            // If not JSON, handle as HTML response
            const parser = new DOMParser();
            const doc = parser.parseFromString(responseText, 'text/html');
            const errorMessage = doc.querySelector('.error-message')?.textContent || 
                               'Failed to update profile. Please try again.';
            alert(errorMessage);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
}