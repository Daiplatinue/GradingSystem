export class FormHandler {
    constructor(formId, modal) {
        this.form = document.getElementById(formId);
        this.modal = modal;
        this.init();
    }

    init() {
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
    }

    async handleSubmit(e) {
        e.preventDefault();
        const formData = new FormData(this.form);

        try {
            const response = await fetch('../doctor/process-checkup.php', {
                method: 'POST',
                body: formData
            });

            const data = await this.parseResponse(response);

            if (data.success) {
                alert(data.message);
                this.modal.close();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while processing your request');
        }
    }

    async parseResponse(response) {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        }

        const text = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, 'text/html');
        const message = doc.body.textContent || text;

        return {
            success: response.ok,
            message: message.trim()
        };
    }
}