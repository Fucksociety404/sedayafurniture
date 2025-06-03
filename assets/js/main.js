
function setupProductCardHover() {
  document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
      card.querySelector('.btn-success').style.display = 'block';
    });
    card.addEventListener('mouseleave', () => {
      card.querySelector('.btn-success').style.display = 'none';
    });
  });
}

setupProductCardHover();


function setupButtonHover() {
  document.querySelectorAll('.product-card').forEach(card => {
    const button = card.querySelector('.btn-success');
    card.addEventListener('mouseenter', () => {
      button.style.display = 'block';
    });
    card.addEventListener('mouseleave', () => {
      button.style.display = 'none';
    });
  });
}

setupButtonHover();