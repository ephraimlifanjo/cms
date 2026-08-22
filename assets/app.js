document.addEventListener('submit', (event) => {
  const message = event.target?.dataset?.confirm;
  if (message && !window.confirm(message)) event.preventDefault();
});

const imageUrl = document.querySelector('#article-image-url');
const imageFile = document.querySelector('#article-image-file');
const imagePreview = document.querySelector('#article-image-preview');
const imagePlaceholder = document.querySelector('#article-image-placeholder');

function showImagePreview(src) {
  if (!imagePreview || !imagePlaceholder) return;
  const value = String(src || '').trim();
  if (!value) {
    imagePreview.hidden = true;
    imagePreview.removeAttribute('src');
    imagePlaceholder.hidden = false;
    return;
  }
  imagePreview.src = value;
  imagePreview.hidden = false;
  imagePlaceholder.hidden = true;
}

if (imageUrl) {
  imageUrl.addEventListener('input', () => showImagePreview(imageUrl.value));
}

if (imageFile) {
  imageFile.addEventListener('change', () => {
    const file = imageFile.files?.[0];
    if (!file) {
      showImagePreview(imageUrl?.value || '');
      return;
    }
    if (!file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = () => showImagePreview(reader.result);
    reader.readAsDataURL(file);
  });
}

if (imagePreview) {
  imagePreview.addEventListener('error', () => {
    imagePreview.hidden = true;
    if (imagePlaceholder) imagePlaceholder.hidden = false;
  });
}
