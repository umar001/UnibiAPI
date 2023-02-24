$(document).ready( function(){
    $('body').on('click','.user-roles-d', function (e) { 
        e.preventDefault(); 
        let uid = $(this).attr('data-id');
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
        });
        $.ajax({
            type: "post",
            url: "setting/user-data",
            data: { 'user_id': uid },
            dataType: "json",
            beforeSend: function () {
                // 
                $(".loader-wrapper").fadeIn("slow");
            },
            success: function (response) {
                console.log(response);
                $('body').find('#userModel .modal-body').html(response.html);
                if($('.user-role-d').val() == 'Admin' || $('.user-role-d').val() == 'Unverified'){
                    $('.user-permission-d').hide();
                }
                $('#userModel').modal('show');
                $('.category-select-multiple').select2({
                });
                // $('#articleModel .modal-content').html(response.html)
            },
            complete: function () {
                $(".loader-wrapper").fadeOut("slow");
            }
        });

    });
    $('.category-select-multiple').select2({
    });
    $('body').on('change','.user-role-d', function () {  
        if($(this).val() == 'Admin' || $(this).val() == 'Unverified'){
            $('.user-permission-d').hide();
        }else{
            $('.user-permission-d').show();
        }
    });

    $('body').on('submit','#role_form', function (e) {  
        e.preventDefault(); 
        $.ajax({
            type: "post",
            url: "setting/update-roles",
            data: new FormData(this),
            dataType: "json",
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {
                $(".loader-wrapper").fadeIn("slow");
            },
            success: function (response) {
                location.reload();
            },
            complete: function () {
                $(".loader-wrapper").fadeOut("slow");
            }
        });
    })
    $('body').on('click','.user_delete', function () {  
        let uid = $(this).attr('data-id');
        $('body').find('#deleteModal .confirm-delete').attr('data-id',uid);
        $('#deleteModal').modal('show');
    })
    $('body').on('click','.confirm-delete',function () { 
        let uid = $(this).attr('data-id');
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
        });
        $.ajax({
            type: "post",
            url: "setting/user-delete",
            data: { 'user_id': uid },
            dataType: "json",
            beforeSend: function () {
                $(".loader-wrapper").fadeIn("slow");
                // 
            },
            success: function (response) {
                location.reload();
                // $('#articleModel .modal-content').html(response.html)
            },
            complete: function () {
                $(".loader-wrapper").fadeOut("slow");
                // $(".loader-overlay").hide();
            }
        });
     })


    
});