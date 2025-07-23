document.addEventListener('DOMContentLoaded', function() {
    // Fetch view count from the database
    fetch('api/counter.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('view-count').innerText = data.count;
        })
        .catch(error => console.error('Error fetching view count:', error));
});