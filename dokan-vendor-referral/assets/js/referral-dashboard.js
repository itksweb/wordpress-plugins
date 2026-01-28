let activeLink = document.getElementById('active-ref-link').value;

function showToast(message, isError = false) {
    const toast = document.getElementById('bk-toast');
    const msgEl = document.getElementById('toast-msg');
    
    // Update Content
    msgEl.innerText = message;
    toast.style.background = isError ? "#ff4d4f" : "#1a1a1a";
    
    const icon = toast.querySelector('i');
    icon.className = isError ? "fas fa-exclamation-circle" : "fas fa-check-circle";
    icon.style.color = isError ? "#fff" : "#25D366";
    
    // Animate In: Keeping the X centering while moving Y
    toast.style.transform = "translate(-50%, 0)";
    
    // Animate Out
    setTimeout(() => { 
        toast.style.transform = "translate(-50%, -150px)"; 
    }, 3000);
}

function generateProductLink() {
    const inputField = document.getElementById('product-input');
    const rawUrl = inputField.value.trim();

    if (!rawUrl) { showToast("Please paste a URL", true); return; }

    // VALIDATOR: Check if URL belongs to your site
    try {
        const urlObj = new URL(rawUrl);
        if (urlObj.hostname !== bk-vars.siteDomain) {
            showToast("Use links from " + bk-vars.siteDomain + " only", true);
            return;
        }
    } catch (e) {
        showToast("Invalid URL format", true);
        return;
    }

    const separator = rawUrl.includes('?') ? '&' : '?';
    activeLink = rawUrl + separator + 'ref=' + bk-vars.vendorRefId;
    navigator.clipboard.writeText(activeLink);
    showToast("Promo Link Generated & Copied!");
    inputField.value = ""; // Clear for next use
}
function copyDynamicLink(btn) {
    navigator.clipboard.writeText(activeLink);
    // copyToClipboard(activeLink);
    const originalText = btn.innerText;
    btn.innerText = "Saved!";
    btn.style.background = "#25D366";
    showToast("General Link Copied!");
    setTimeout(() => { btn.innerText = originalText; btn.style.background = "#333"; }, 2000);
}
function switchLinkType(type) {
    const genArea = document.getElementById('general-link-area');
    const prodArea = document.getElementById('product-link-area');
    const tabGen = document.getElementById('tab-gen');
    const tabProd = document.getElementById('tab-prod');

    if (type === 'product') {
        // Toggle Areas
        genArea.style.display = 'none';
        prodArea.style.display = 'block';

        // Update Tab Classes
        tabProd.classList.add('active');
        tabGen.classList.remove('active');
    } else {
        // Toggle Areas
        genArea.style.display = 'block';
        prodArea.style.display = 'none';

        // Update Tab Classes
        tabGen.classList.add('active');
        tabProd.classList.remove('active');

        // Reset activeLink to the default general referral URL
        activeLink = document.getElementById('active-ref-link').value;
    }
}
function socialShare(platform) {
    const text = encodeURIComponent("Check this out!");
    const finalLink = encodeURIComponent(activeLink);
    let url = "";

    switch (platform) {
        case 'wa': url = `https://api.whatsapp.com/send?text=${text}%20${finalLink}`; break;
        case 'fb': url = `https://www.facebook.com/sharer/sharer.php?u=${finalLink}`; break;
        case 'tw': url = `https://twitter.com/intent/tweet?url=${finalLink}&text=${text}`; break;
        case 'em': url = `mailto:?subject=Recommendation&body=${text}%20${finalLink}`; break;
    }
    window.open(url, '_blank');
}

