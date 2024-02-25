var LevelUp = {

    User: {

        dp: null,

        toolid: 0,
        realm: 0,
        character: 0,
        faction: 0,

        initialize: function (config) {
            this.dp = config.dp;
            this.price = config.price;
            this.realm = config.realm;
        }
    },

    RealmChanged: function (selectField) {
        var selected = $(selectField).find('option:selected');

        if (typeof selected != 'undefined' && selected.length > 0) {
            var visible = $('.character_select:visible');
            var id = parseInt(selected.val());

            if (typeof visible != 'undefined' && visible.length > 0) {
                visible.fadeOut('fast', function () {
                    $('#character_select_' + id).fadeIn('fast');
                });
            } else {
                $('#character_select_' + id).fadeIn('fast');
            }

            this.User.realm = id;
        }
    },
    CharacterChanged: function (selectField, realmId) {
        var selected = $(selectField).find('option:selected');

        if (typeof selected != 'undefined' && selected.length > 0) {
            var guid = parseInt(selected.val());

            this.User.character = guid;

        }

        this.User.realm = realmId;
    },

    CharacterPrice: function (selectField) {
        var selected = $(selectField).find('option:selected');

        if (typeof selected != 'undefined' && selected.length > 0) {
            var price = parseInt(selected.val());

            this.User.price = price;

        }


    },


    busy: false,

    Submit: function (form) {
        if (this.busy)
            return;

        //Check if we have selected realm
        if (this.User.realm == 0) {



            Swal.fire({
                icon: 'error',
                title: 'character tools',
                text: lang("no_realm_selected", "levelup"),
            })
            return;
        }

        //Check if we have selected character
        if (this.User.character == 0) {

            Swal.fire({
                icon: 'error',
                title: 'character tools',
                text: lang("no_char_selected", "levelup"),
            })
            return;
        }

        var CanAfford = false;

        if (this.User.price == 0) {
            CanAfford = true;
        } else {
            if (LevelUp.User.dp < this.User.price) {

                Swal.fire({
                    icon: 'error',
                    title: 'character tools',
                    text: lang("cant_afford", "levelup"),
                })


            } else {
                CanAfford = true;
            }
        }

        if (CanAfford) {
            // Make the user confirm the purchase

            Swal.fire({

                title: lang("want_to_buy", "levelup"),
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.isConfirmed) {

                    // Mark as busy
                    LevelUp.busy = true;
                    LevelUp.DisplayMessage(lang('processing', 'levelup') + '<br><img src="' + Config.image_path + 'ajax.gif" />');

                    // Post the data
                    $.post(Config.URL + "levelup/submit", {

                        realm: LevelUp.User.realm,
                        guid: LevelUp.User.character,
                        price: LevelUp.User.price,
                        csrf_token_name: Config.CSRF
                    }, function (data) {
                        // Display the returned message
                        LevelUp.DisplayMessage(data);

                        // Mark the store as no longer bussy
                        LevelUp.busy = false;
                    });
                }
            });
        }
    },

    DisplayMessage: function (data) {
        if ($('#character_tools_message').is(':visible')) {
            $('#character_tools_message').fadeOut('fast', function () {
                $('#character_tools_message').html(data);
                $('#character_tools_message').fadeIn('fast');
            });
        } else {
            $('#character_tools').fadeOut('fast', function () {
                $('#character_tools_message').html(data);
                $('#character_tools_message').fadeIn('fast');
            });
        }
    },

    Back: function () {
        window.location = Config.URL + "levelup";
    }
}