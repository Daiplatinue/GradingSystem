export class Modal {
    constructor(modalId) {
        this.modal = document.getElementById(modalId);
        this.closeBtn = this.modal.querySelector('.modal-close');
        this.form = document.getElementById('updateForm');
        
        this.init();
    }

    init() {
        this.closeBtn.addEventListener('click', () => this.close());
        window.addEventListener('click', (event) => {
            if (event.target === this.modal) {
                this.close();
            }
        });
    }

    show(checkupId) {
        this.modal.classList.remove('hidden');
        document.getElementById('checkup_id').value = checkupId;
    }

    close() {
        this.modal.classList.add('hidden');
        if (this.form) {
            this.form.reset();
        }
    }
}