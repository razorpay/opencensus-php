<!DOCTYPE html>
<html lang="en">
<head>
    <script>
        let responseVal = @json($response)
        // Function to set a key in local storage
        function setKeyInLocalStorage(key, value) {
            localStorage.setItem(key, value);
        }
        function closeWindow(){
            window.close();
        }
        // Function to execute when the page loads
        window.onload = function() {
            setKeyInLocalStorage("loginDetails",  JSON.stringify(responseVal));
        };
        setTimeout(closeWindow, 5000);
    </script>
</head>
<body>
Redirecting to Admin Dashboard
</body>
</html>
