document.addEventListener("DOMContentLoaded", () => {
  const file = document.querySelector("[data-file-name]");
  if (file) {
    file.addEventListener("change", () => {
      const label = document.querySelector("[data-file-label]");
      if (label && file.files[0]) label.textContent = file.files[0].name;
    });
  }
});
