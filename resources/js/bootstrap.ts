import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

document.addEventListener("DOMContentLoaded", function () {
    // Function to smoothly scroll into view
    function ScrollIntoView(elem) {
        let ele = document.querySelector(elem);
        if (ele) {
            ele.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    }

    // Add event listener to all links with a hash
    document.querySelectorAll('a[href^="#"]').forEach((link) => {
        link.addEventListener("click", function (event) {
            event.preventDefault();
            console.log("clicked");
            const hash = this.getAttribute("href");
            ScrollIntoView(hash);
            history.pushState(null, null, hash); // Update URL without jumping
        });
    });

    // Check URL hash on page load and scroll to that section
    const hashOnLoad = window.location.hash;
    if (hashOnLoad) {
        setTimeout(() => {
            ScrollIntoView(hashOnLoad);
        }, 100);
    }
});
