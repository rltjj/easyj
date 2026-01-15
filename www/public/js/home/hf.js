async function loadHTML(id, file) {
  try {
    const container = document.getElementById(id);
    if (!container) return;

    const res = await fetch(file);
    if (!res.ok) throw new Error(`Failed to fetch ${file}`);
    container.innerHTML = await res.text();

  } catch (err) {
    console.error(err);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  loadHTML("header", "header.html");
  loadHTML("footer", "footer.html");
});

document.addEventListener("click", (e) => {
  const hamburger = document.getElementById("hamburgerBtn");
  const menu = document.getElementById("mobileMenu");
  const overlay = document.getElementById("menuOverlay");

  if (!hamburger || !menu) return;

  if (e.target === hamburger) {
    const isOpen = menu.classList.toggle("active");
    hamburger.textContent = isOpen ? "✕" : "☰";
    if (overlay) overlay.classList.toggle("active", isOpen);
  }

  if (e.target === overlay) {
    menu.classList.remove("active");
    e.target.classList.remove("active");
    hamburger.textContent = "☰";
  }
});

document.addEventListener("click", (e) => {
  const menu = document.getElementById("mobileMenu");

  if (menu && e.target.tagName === "A") {
    menu.classList.remove("active");
  }
});

