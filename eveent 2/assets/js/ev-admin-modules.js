document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.ev-switch input');
    
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const card = this.closest('.ev-module-card');
            const statusText = card.querySelector('.ev-status-text');
            
            if(this.checked) {
                card.classList.add('active');
                statusText.textContent = 'Aktif';
            } else {
                card.classList.remove('active');
                statusText.textContent = 'Nonaktif';
            }
        });
    });
});