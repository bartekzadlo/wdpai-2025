const header = document.querySelector('#title');
console.log(header); // To pokaże element lub null w konsoli

// Wykonuj kod tylko jeśli element istnieje na tej podstronie
if (header) {
    header.addEventListener('click', () => {
        header.style.color = 'green';
    });
}