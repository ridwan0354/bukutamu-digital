function applyAccessGranted(isInitial) {
    const loginWrap = document.querySelector('.ewf-privacy-login-wrap:not(.ewf-guest-not-found-wrap)');
    
   
    document.body.classList.remove('ewf-show-wall');

    if (loginWrap) {
        if (isInitial) {
           
            loginWrap.classList.add('ewf-hidden');
        } else {
           
            loginWrap.style.opacity = '0';
            setTimeout(() => {
                loginWrap.classList.add('ewf-hidden');
            }, 500);
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    
    if (typeof EWF_PRIVACY_DATA === 'undefined') return;

    const input = document.getElementById('ewf_barcode_input');
    const button = document.getElementById('ewf_submit_barcode');
    const messageArea = document.getElementById('ewf_message_area');
    const loginWrap = document.querySelector('.ewf-privacy-login-wrap:not(.ewf-guest-not-found-wrap)');
    
  
    const validCode = EWF_PRIVACY_DATA.validCode;
    const postId = EWF_PRIVACY_DATA.postId;

    
    const attemptsKey = 'ewf_login_attempts_' + postId;
    const lockoutKey = 'ewf_login_lockout_' + postId;
    const maxAttempts = 3;
    const lockTime = 3600000; 

    if (!loginWrap) return;
    
   
    if (sessionStorage.getItem('ewf_access_granted_' + postId) === 'true') {
       
        applyAccessGranted(true);
        return;
    }
    
    
    loginWrap.classList.remove('ewf-hidden');
    document.body.classList.add('ewf-show-wall');

    if (!button || !input) return;

    function updateLockoutStatus() {
        const lockoutTime = localStorage.getItem(lockoutKey);
        if (lockoutTime) {
            const remainingTime = lockoutTime - Date.now();
            if (remainingTime > 0) {
                const minutes = Math.ceil(remainingTime / 60000);
                messageArea.style.color = 'red';
                messageArea.innerText = `Terlalu banyak percobaan. Coba lagi dalam ${minutes} menit.`;
                button.disabled = true;
                input.disabled = true;
                return true;
            } else {
                
                localStorage.removeItem(lockoutKey);
                localStorage.removeItem(attemptsKey);
                button.disabled = false;
                input.disabled = false;
                messageArea.innerText = '';
                return false;
            }
        }
        return false;
    }

   
    if (updateLockoutStatus()) return;


    function checkBarcode() {
        if (updateLockoutStatus()) return;

        const enteredCode = input.value.trim().toUpperCase();
        let attempts = parseInt(localStorage.getItem(attemptsKey)) || 0;

        if (enteredCode === validCode) {
           
            messageArea.style.color = 'green';
            messageArea.innerText = 'Kode diterima! Memuat halaman...';

            
            sessionStorage.setItem('ewf_access_granted_' + postId, 'true');
            
            
            localStorage.removeItem(attemptsKey);
            localStorage.removeItem(lockoutKey);
            
            
            applyAccessGranted(false);

        } else {
            
            attempts++;
            localStorage.setItem(attemptsKey, attempts);
            input.value = '';

            if (attempts >= maxAttempts) {
                
                const lockoutUntil = Date.now() + lockTime;
                localStorage.setItem(lockoutKey, lockoutUntil);
                updateLockoutStatus();
            } else {
                
                const remaining = maxAttempts - attempts;
                messageArea.style.color = 'red';
                messageArea.innerText = `Kode tidak valid. Sisa percobaan: ${remaining} kali.`;
            }
        }
    }

    
    button.addEventListener('click', checkBarcode);
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            checkBarcode();
        }
    });
});