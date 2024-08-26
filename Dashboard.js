/* Created by Tivotal */

const body = document.querySelector("body");
const modeToggle = body.querySelector(".mode-toggle");
const sidebar = body.querySelector("nav");
const sidebarToggle = body.querySelector(".sidebar-toggle");
const icon = document.getElementById("sidebar-icon");

let getMode = localStorage.getItem("mode");
if (getMode && getMode === "dark") {
	body.classList.toggle("dark");
}

let getStatus = localStorage.getItem("status");
if (getStatus && getStatus === "close") {
	sidebar.classList.toggle("close");
}

modeToggle.addEventListener("click", () => {
	body.classList.toggle("dark");
	if (body.classList.contains("dark")) {
		localStorage.setItem("mode", "dark");
	} else {
		localStorage.setItem("mode", "light");
	}
});

sidebarToggle.addEventListener("click", () => {
	sidebar.classList.toggle("close");
	if (sidebar.classList.contains("close")) {
		localStorage.setItem("status", "close");
	} else {
		localStorage.setItem("status", "open");
	}
});

// sidebarToggle.addEventListener('click', () => {
//     if(icon.style.display = 'none'){
//         icon.style.display = 'block'
//     }else{
//         icon.style.display = 'none'
//     }
// });

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = body.querySelector("nav");
    const sidebarToggle = body.querySelector(".sidebar-toggle");
    const icon = sidebarToggle.getElementById('sidebar-icon'); 

    sidebarToggle.addEventListener('click', () => {
        if (icon.style.display === 'none') {
            icon.style.display = 'block';
        } else {
            icon.style.display = 'none';
        }

        // Toggle the sidebar visibility
        sidebar.classList.toggle('open');
        document.body.classList.toggle('sidebar-open'); // Optional: Hide overflow when sidebar is open
    });
});

// // Toggle dropdown menu visibility
// document.addEventListener('DOMContentLoaded', function() {
//     const dropdowns = document.querySelectorAll('.dropdown');

//     dropdowns.forEach(dropdown => {
//         dropdown.addEventListener('click', function() {
//             this.querySelector('.dropdown-menu').classList.toggle('show');
//         });
//     });

//     // Close dropdowns when clicking outside
//     document.addEventListener('click', function(event) {
//         dropdowns.forEach(dropdown => {
//             if (!dropdown.contains(event.target)) {
//                 dropdown.querySelector('.dropdown-menu').classList.remove('show');
//             }
//         });
//     });
// });