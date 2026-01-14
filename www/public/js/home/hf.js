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
