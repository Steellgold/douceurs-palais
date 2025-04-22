const Cart = {
  init: function() {
    this.bindAddToCartButtons();
  },

  bindAddToCartButtons: function() {
    const addToCartButtons = document.querySelectorAll('[data-add-to-cart]');
    addToCartButtons.forEach(button => {
      button.addEventListener('click', function(event) {
        event.preventDefault();
        const productId = this.dataset.productId;
        let quantity = 1;
        const quantityInput = document.querySelector('input[name="quantity"]');
        if (quantityInput) {
          quantity = parseInt(quantityInput.value);
          if (isNaN(quantity) || quantity < 1) {
            quantity = 1;
          } else if (quantity > 99) {
            quantity = 99;
          }
        }
        Cart.addItem(productId, quantity, this);
      });
    });
  },

  addItem: function(productId, quantity, button) {
    if (button) {
      button.disabled = true;
      button.classList.add('opacity-50');
      const originalText = button.innerHTML;
      button.innerHTML = '<i class="lucide lucide-loader-2 animate-spin"></i> Ajout en cours...';

      fetch('/cart/api/add', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          productId: productId,
          quantity: quantity
        })
      })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              Cart.updateCartDisplay(data);
              // Cart.showNotification('Produit ajouté au panier', 'success');
            } else {
              console.error(data.message);
              // Cart.showNotification('Erreur: ' + data.message, 'error');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            // Cart.showNotification('Une erreur est survenue', 'error');
          })
          .finally(() => {
            if (button) {
              button.disabled = false;
              button.classList.remove('opacity-50');
              button.innerHTML = originalText;
            }
          });
    }
  },

  updateCartDisplay: function(data) {
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(element => {
      element.textContent = data.itemCount;
      if (data.itemCount > 0) {
        element.classList.remove('hidden');
      } else {
        element.classList.add('hidden');
      }
    });
  },

  // showNotification: function(message, type = 'success') {
  //   let notification = document.querySelector('.cart-notification');
  //   if (notification) {
  //     notification.remove();
  //   }
  //   notification = document.createElement('div');
  //   notification.className = 'cart-notification fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg text-white transition-opacity duration-300';
  //   if (type === 'error') {
  //     notification.classList.add('bg-red-500');
  //   } else {
  //     notification.classList.add('bg-green-500');
  //   }
  //   notification.textContent = message;
  //   document.body.appendChild(notification);
  //   setTimeout(() => {
  //     notification.classList.add('opacity-0');
  //     setTimeout(() => {
  //       notification.remove();
  //     }, 300);
  //   }, 3000);
  // },
};

document.addEventListener('DOMContentLoaded', function() {
  Cart.init();
});