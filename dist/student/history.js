document.addEventListener('DOMContentLoaded', function () {
    // Search functionality
    const searchInput = document.querySelector('input[type="text"]');
    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            const searchTerm = e.target.value.toLowerCase();
            console.log('Searching for:', searchTerm);
        });
    }

    // Export records functionality
    const exportButton = document.querySelector('button');
    if (exportButton && exportButton.textContent.includes('Export Records')) {
        exportButton.addEventListener('click', function () {
            console.log('Exporting records...');
        });
    }

    // Filter functionality
    const filterSelect = document.querySelector('select');
    if (filterSelect) {
        filterSelect.addEventListener('change', function (e) {
            const selectedFilter = e.target.value;
            console.log('Filter selected:', selectedFilter);
        });
    }

    // Year filter buttons
    const yearButtons = Array.from(document.querySelectorAll('button')).filter(button =>
        button.textContent.startsWith('202')
    );
    if (yearButtons.length) {
        yearButtons.forEach(button => {
            button.addEventListener('click', function () {
                yearButtons.forEach(btn => btn.classList.remove('bg-blue-600'));
                this.classList.add('bg-blue-600');
                console.log('Year selected:', this.textContent);
            });
        });
    }

    // View report/x-ray buttons
    const viewButtons = Array.from(document.querySelectorAll('button')).filter(button =>
        button.textContent.includes('View')
    );
    if (viewButtons.length) {
        viewButtons.forEach(button => {
            button.addEventListener('click', function () {
                const type = this.textContent.includes('Report') ? 'report' : 'x-ray';
                console.log('Viewing', type);
            });
        });
    }
});