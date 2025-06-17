document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - app.js está corriendo.');

    const confirmationModal = document.getElementById('confirmationModal');
    console.log('confirmationModal', confirmationModal);
    const closeButton = document.querySelector('.close-button');
    console.log('closeButton', closeButton);
    const noButton = document.querySelector('.no-button');
    console.log('noButton', noButton);
    const siButton = document.querySelector('.si-button');
    console.log('siButton', siButton);
    const modalTableNumberSpan = document.querySelector('.modal-table-number span');
    console.log('modalTableNumberSpan', modalTableNumberSpan);
    const modalPedidoList = document.querySelector('.modal-pedido-list');
    console.log('modalPedidoList', modalPedidoList);
    const listoButtons = document.querySelectorAll('.card-historial-listo-btn');
    console.log('listoButtons', listoButtons);

    // Nuevo modal para mensaje de éxito
    const successModal = document.getElementById('successModal');
    console.log('successModal', successModal);
    const successMessageSpan = document.querySelector('#successModal .modal-table-number span');
    console.log('successMessageSpan', successMessageSpan);
    const successCloseButton = document.querySelector('#successModal .close-button');
    console.log('successCloseButton', successCloseButton);

    let currentPedidoId = null; // Variable para almacenar el ID del pedido actual
    let currentMesaNumero = null; // Variable para almacenar el número de mesa actual

    listoButtons.forEach(button => {
        console.log('Adding event listener to button:', button);
        button.addEventListener('click', function() {
            console.log('Button clicked!');
            const cardHistorial = this.closest('.card-historial');
            console.log('cardHistorial', cardHistorial);
            currentPedidoId = cardHistorial.dataset.pedidoId;
            currentMesaNumero = cardHistorial.dataset.mesaNumero; // Almacenar el número de mesa
            console.log('Pedido ID:', currentPedidoId, 'Mesa Numero:', currentMesaNumero);

            const productos = [];

            cardHistorial.querySelectorAll('tbody tr').forEach(row => {
                const cantidadElement = row.querySelector('[data-cantidad]');
                const nombreProductoElement = row.querySelector('[data-nombre-producto]');
                
                if (cantidadElement && nombreProductoElement) {
                    const cantidad = cantidadElement.dataset.cantidad < 10 ? `0${cantidadElement.dataset.cantidad}` : cantidadElement.dataset.cantidad;
                    const nombreProducto = nombreProductoElement.dataset.nombreProducto;
                    productos.push(`${cantidad} ${nombreProducto}`);
                }
            });

            console.log('Productos:', productos);
            modalTableNumberSpan.textContent = currentMesaNumero;
            modalPedidoList.innerHTML = ''; // Limpiar lista anterior
            productos.forEach(producto => {
                const li = document.createElement('li');
                li.textContent = producto;
                modalPedidoList.appendChild(li);
            });

            console.log('Attempting to display confirmation modal...');
            confirmationModal.style.display = 'flex';
            console.log('Confirmation modal display set to flex.');
        });
    });

    // Cerrar modal de confirmación con el botón de cierre
    closeButton.addEventListener('click', function() {
        console.log('Close button clicked - confirmation modal.');
        confirmationModal.style.display = 'none';
    });

    // Cerrar modal de confirmación con el botón 'No'
    noButton.addEventListener('click', function() {
        console.log('No button clicked - confirmation modal.');
        confirmationModal.style.display = 'none';
    });

    // Enviar el pedido cuando se hace clic en 'Sí'
    siButton.addEventListener('click', function() {
        console.log('Yes button clicked - confirmation modal.');
        if (currentPedidoId) {
            // Añadir el token CSRF (importante para Laravel)
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(`/barra/marcar-pedido-listo/${currentPedidoId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                // Si necesitas enviar más datos en el body, agrégalos aquí
                // body: JSON.stringify({ /* tus datos */ })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Success:', data);
                confirmationModal.style.display = 'none'; // Ocultar modal de confirmación
                
                // Mostrar modal de éxito
                if (successModal && successMessageSpan) {
                    successMessageSpan.textContent = currentMesaNumero;
                    successModal.style.display = 'flex';
                    console.log('Success modal displayed.');
                    // Ocultar modal de éxito automáticamente después de 2 segundos
                    setTimeout(() => {
                        successModal.style.display = 'none';
                        console.log('Success modal hidden after timeout.');
                        window.location.reload(); // Recargar la página después de que el modal desaparezca
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Hubo un error al marcar el pedido como listo.');
            });
        }
    });

    // Cerrar modal de éxito con el botón de cierre (X)
    if (successCloseButton) {
        successCloseButton.addEventListener('click', function() {
            console.log('Close button clicked - success modal.');
            successModal.style.display = 'none';
            window.location.reload(); // Recargar la página después de cerrar el modal de éxito
        });
    }

    // Cerrar modal de éxito con el botón 'Aceptar' (que tiene la clase 'si-button' dentro del modal de éxito)
    const acceptButton = document.querySelector('#successModal .si-button');
    if (acceptButton) {
        acceptButton.addEventListener('click', function() {
            console.log('Accept button clicked - success modal.');
            successModal.style.display = 'none';
            window.location.reload(); // Recargar la página después de cerrar el modal de éxito
        });
    }
    
    // Cerrar los modales si se hace clic fuera de ellos
    window.addEventListener('click', function(event) {
        if (event.target === confirmationModal) {
            console.log('Clicked outside confirmation modal.');
            confirmationModal.style.display = 'none';
        }
        if (event.target === successModal) {
            console.log('Clicked outside success modal.');
            successModal.style.display = 'none';
            window.location.reload(); // Recargar la página si se cierra el modal de éxito haciendo clic fuera
        }
    });
});
