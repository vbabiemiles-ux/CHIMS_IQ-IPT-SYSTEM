$(document).ready(function () {

    $(".editUserBtn").click(function () {

        var id = $(this).data("id");
        $.ajax({
            url: "/controllers/users/readOne.php", 
            type: "GET",
            data: { 
                id: id,
                csrf_token: $("#csrf_token").val()
            },
            dataType: "json",

            success: function (user) {

                $("#user_id").val(user.id);
                $("#user_name").val(user.name);
                $("#user_email").val(user.email);

            },

            error: function (xhr) {
                Swal.fire({
                    title: "Error!",
                    text: xhr.responseText,
                    icon: "error"
                });
            }
        });

    });


    $(".deleteUserBtn").click(function () {

        var id = $(this).data("id");
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "/controllers/users/delete.php", 
                    type: "POST",
                    data: { 
                        id: id, 
                        csrf_token: $("#csrf_token").val()
                    },
                    dataType: "json",
                    success: function (user) {

                        if(user.status){
                            let timerInterval;
                            Swal.fire({
                            icon: "success",
                            title: "Success!",
                            html: user.message + " This will close in <b></b> milliseconds.",
                            timer: 2000,
                            timerProgressBar: true,
                            didOpen: () => {
                                Swal.showLoading();
                                const timer = Swal.getPopup().querySelector("b");
                                timerInterval = setInterval(() => {
                                timer.textContent = `${Swal.getTimerLeft()}`;
                                }, 100);
                            },
                            willClose: () => {
                                clearInterval(timerInterval);
                            }
                            }).then((result) => {
                                window.location.reload();
                            });
                        }else{
                            Swal.fire({
                                title: "Error!",
                                text: user.message,
                                icon: "error"
                            });
                        }
                    },

                    error: function (xhr) {
                        Swal.fire({
                            title: "Error!",
                            text: xhr.responseText,
                            icon: "error"
                        });
                    }
                });
            }
        });

    });



});