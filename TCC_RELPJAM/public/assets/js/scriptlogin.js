// ======================
// ELEMENTOS PRINCIPAIS
// ======================
if (window.__loginScriptLoaded) {
    console.warn("scriptlogin já carregado");
} else {
    window.__loginScriptLoaded = true;

    const container = document.getElementById("container");
    const registerBtn = document.getElementById("register");
    const loginBtn = document.getElementById("login");

    if (registerBtn) {
        registerBtn.addEventListener("click", () => {
            container?.classList.add("active");
        });
    }

    if (loginBtn) {
        loginBtn.addEventListener("click", () => {
            container?.classList.remove("active");
        });
    }
}
console.log("scriptlogin carregado");
// ======================
// TROCA LOGIN / CADASTRO
// ======================

if (registerBtn) {
    registerBtn.addEventListener("click", () => {
        container.classList.add("active");
    });
}

if (loginBtn) {
    loginBtn.addEventListener("click", () => {
        container.classList.remove("active");
    });
}

// ======================
// FORM LOGIN
// ======================

const loginForm = document.getElementById("loginForm");

if (loginForm) {

    loginForm.addEventListener("submit", function (e) {

        e.preventDefault();

        const email =
            document.getElementById("loginEmail").value;

        const password =
            document.getElementById("loginPassword").value;

        console.log("Login:", email, password);

        // Futuro:
        // fetch("login.php");
    });
}

// ======================
// FORM CADASTRO
// ======================

const signupForm = document.getElementById("signupForm");

if (signupForm) {

    signupForm.addEventListener("submit", function (e) {

        e.preventDefault();

        const name =
            document.getElementById("signupName").value;

        const email =
            document.getElementById("signupEmail").value;

        const password =
            document.getElementById("signupPassword").value;

        console.log(
            "Cadastro:",
            name,
            email,
            password
        );

        // Futuro:
        // fetch("cadastro.php");
    });
}

// ======================
// GOOGLE LOGIN
// ======================

function handleCredentialResponse(response) {

    try {

        const payload = JSON.parse(
            decodeURIComponent(
                escape(
                    atob(
                        response.credential
                            .split(".")[1]
                    )
                )
            )
        );

        console.log("Nome:", payload.name);
        console.log("Email:", payload.email);

        showUser(payload);

    } catch (error) {

        console.error(
            "Erro ao processar login Google:",
            error
        );
    }
}

// ======================
// EXIBIR USUÁRIO
// ======================

function showUser(user) {

    const existing =
        document.getElementById(
            "google-user-box"
        );

    if (existing) {
        existing.remove();
    }

    const box =
        document.createElement("div");

    box.id = "google-user-box";

    box.innerHTML = `
        <img src="${user.picture}" alt="${user.name}">
        <div>
            <strong>${user.name}</strong>
            <span>${user.email}</span>
        </div>
    `;

    document.body.appendChild(box);
}

// ======================
// FUTUROS LOGINS
// ======================

function loginGithub() {
    console.log("GitHub OAuth");
}

function loginFacebook() {
    console.log("Facebook OAuth");
}

function loginLinkedIn() {
    console.log("LinkedIn OAuth");
}
document.addEventListener("mousemove",(e)=>{

document.body.style.setProperty("--x",e.clientX+"px");

document.body.style.setProperty("--y",e.clientY+"px");

});