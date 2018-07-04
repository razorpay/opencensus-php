<?php
    $secondary_color = '#6F7691';
    $text_red        = '#F05150';
?>

<style>
    .form-group {
        position: relative;
        margin-bottom: 28px;
        color: {{$secondary_color}};
    }

    .form-group label {
        display: inline-block;
        width: 140px;
        font-size: 14px;
        margin-right: 20px;
        line-height: 20px;
        text-align: right;
        color: {{$secondary_color}};
    }

    .form-group label, .form-group .form-control {
        vertical-align: middle;
        color: inherit;
    }

    .form-group .form-control {
        height: 36px;
        width: 306px;
        border: 1px solid rgba(0,0,0,0.12);
        border-radius: 2px;
        background-color: #FFFFFF;
        outline: none;
        font-size: 14px;
        padding: 0 12px;
        line-height: 18px;
    }

    .form-group input:focus {
        border: 1px solid #528FF0;
    }

    .form-group select {
        background: #fff url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAABmJLR0QA/wD/AP+gvaeTAAAAOElEQVRIiWNgGAWjYBQMf9DBwMDwnwDuoNSSBjyGN1BqOD5LqGY4NkuobjiyJTQzfBSMglFAJgAAYQgY/SW0dY0AAAAASUVORK5CYII=) no-repeat 96%;
        -webkit-appearance: none;
        -webkit-border-radius: 2px;
    }

    .form-group .help-block {
        position: absolute;
        margin: 2px 0 0 160px;
        font-size: 12px;
    }

    .form-group .errormsg {
        color: {{$text_red}};
    }

    .form-group.has-error .form-control {
        border-color: {{$text_red}};
    }

    .form-group .icon {
        font-size: 16px;
        position: absolute;
        left: 16px;
        line-height: 36px;
    }

    .form-group .icon + .form-control {
        padding-left: 40px;
    }

    /* Placeholder style */
    ::-webkit-input-placeholder { /* Chrome/Opera/Safari */
        color: rgba(0,0,0,0.3);
    }
    ::-moz-placeholder { /* Firefox 19+ */
        color: rgba(0,0,0,0.3);
    }
    :-ms-input-placeholder { /* IE 10+ */
        color: rgba(0,0,0,0.3);
    }
    :-moz-placeholder { /* Firefox 18- */
        color: rgba(0,0,0,0.3);
    }

    @media (max-width: 924px) {
        .form-group label {
            width: 100%;
            margin-bottom: 4px;
            text-align: left;
        }

        .form-group .form-control {
            width: 100%;
        }

        .form-group .help-block {
            margin-left: 0;
        }
    }
    
</style>