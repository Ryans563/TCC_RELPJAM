document.addEventListener("DOMContentLoaded", () => {

    const buttons = document.querySelectorAll(".tab-btn");
    const tabs = document.querySelectorAll(".tab");

    buttons.forEach(btn => {

        btn.addEventListener("click", () => {

            const target = btn.dataset.tab;

            buttons.forEach(b => b.classList.remove("active"));
            tabs.forEach(t => t.classList.remove("active"));

            btn.classList.add("active");
            document.getElementById(target).classList.add("active");

        });

    });

});