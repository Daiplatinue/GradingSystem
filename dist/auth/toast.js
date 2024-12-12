function showToast(message) {
    var toast = document.getElementById('toast');
    var toastMessage = document.getElementById('toast-message');
    toastMessage.textContent = message;

    toast.classList.remove('opacity-0', 'pointer-events-none');
    toast.classList.add('opacity-100', 'pointer-events-auto');

    setTimeout(function() {
        toast.classList.remove('opacity-100', 'pointer-events-auto');
        toast.classList.add('opacity-0', 'pointer-events-none');
    }, 3000); 
}
