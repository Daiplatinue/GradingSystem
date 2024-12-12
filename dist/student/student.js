document.addEventListener('DOMContentLoaded', function() {
  const dateInput = document.querySelector('input[type="date"]');
  if (dateInput) {
      const today = new Date().toISOString().split('T')[0];
      dateInput.min = today;
  }
});

function openCheckupModal() {
  const modal = document.getElementById('checkupModal');
  modal.classList.remove('hidden');
  setTimeout(() => {
      modal.querySelector('.bg-dark-100').classList.remove('scale-90');
  }, 10);
}

function closeCheckupModal() {
  const modal = document.getElementById('checkupModal');
  modal.querySelector('.bg-dark-100').classList.add('scale-90');
  setTimeout(() => {
      modal.classList.add('hidden');
  }, 300);
}

document.addEventListener('DOMContentLoaded', function() {
  const numbers = document.querySelectorAll('.animate-number');
  
  numbers.forEach(number => {
      const target = parseInt(number.dataset.target);
      let current = 0;
      const increment = target / 20;
      
      const timer = setInterval(() => {
          current += increment;
          number.textContent = Math.round(current);
          
          if (current >= target) {
              number.textContent = target;
              clearInterval(timer);
          }
      }, 50);
  });
});