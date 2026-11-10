// ==========================================
// DUBEY KIRANA STORE
// Main JavaScript File
// ==========================================

document.addEventListener("DOMContentLoaded", function () {

    // ==========================================
    // 1. MOBILE NAVIGATION
    // ==========================================

    const mobileMenuBtn = document.getElementById("mobileMenuBtn");
    const mainNavigation = document.getElementById("mainNavigation");

    if (mobileMenuBtn && mainNavigation) {

        mobileMenuBtn.setAttribute("aria-expanded", "false");

        mobileMenuBtn.addEventListener("click", function () {

            const isMenuOpen = mainNavigation.classList.toggle("show");

            mobileMenuBtn.setAttribute(
                "aria-expanded",
                isMenuOpen ? "true" : "false"
            );

            // Change menu icon
            const icon = mobileMenuBtn.querySelector("i");

            if (icon) {

                if (isMenuOpen) {

                    icon.classList.remove("fa-bars");
                    icon.classList.add("fa-xmark");

                } else {

                    icon.classList.remove("fa-xmark");
                    icon.classList.add("fa-bars");

                }

            }

        });

    }


    // ==========================================
    // 2. CLOSE MOBILE MENU AFTER CLICKING LINK
    // ==========================================

    if (mainNavigation) {

        const navigationLinks = mainNavigation.querySelectorAll("a");

        navigationLinks.forEach(function (link) {

            link.addEventListener("click", function () {

                mainNavigation.classList.remove("show");

                if (mobileMenuBtn) {

                    mobileMenuBtn.setAttribute(
                        "aria-expanded",
                        "false"
                    );

                    const icon = mobileMenuBtn.querySelector("i");

                    if (icon) {

                        icon.classList.remove("fa-xmark");
                        icon.classList.add("fa-bars");

                    }

                }

            });

        });

    }


    // ==========================================
    // 3. CLOSE MENU WHEN CLICKING OUTSIDE
    // ==========================================

    document.addEventListener("click", function (event) {

        if (!mobileMenuBtn || !mainNavigation) {
            return;
        }

        const clickedInsideMenu =
            mainNavigation.contains(event.target);

        const clickedMenuButton =
            mobileMenuBtn.contains(event.target);

        if (
            !clickedInsideMenu &&
            !clickedMenuButton &&
            mainNavigation.classList.contains("show")
        ) {

            mainNavigation.classList.remove("show");

            mobileMenuBtn.setAttribute(
                "aria-expanded",
                "false"
            );

            const icon = mobileMenuBtn.querySelector("i");

            if (icon) {

                icon.classList.remove("fa-xmark");
                icon.classList.add("fa-bars");

            }

        }

    });

});