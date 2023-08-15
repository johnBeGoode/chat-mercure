document.addEventListener("DOMContentLoaded", () => {
    let formSubmitMessage = document.querySelector('#message_Send');
    let formMessageContent = "";

    formSubmitMessage.addEventListener("click", function (e) {
        e.preventDefault();

        formMessageContent = document.querySelector("#message_content").value;

        if (formMessageContent != "") {

            const data = { // La variable data sera envoyée au controller
                'content': formMessageContent // On transmet le message...
            }

            messageToServer(data);
        }
    })

    const messageToServer = (data) => {
    
        const url = window.location.href+ "/post-message";
        formMessageContent = document.querySelector("#message_content");
    
        fetch(url, { // On envoie avec un post nos datas sur le endpoint /message de notre application
            method: 'POST',
            body: JSON.stringify(data) // On envoie les data sous format JSON
        }).then((response) => {
            formMessageContent.value = '';
        }).catch((error) => {
            console.log(error)
        });
    
    }
});
