<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class MccTccMapping
{
    const MCC_CODE        = 'mcc_code';
    const TCC_CODE        = 'tcc_code';
    const TCC_DESCRIPTION = 'tcc_description';

    const MCC_TCC_MAPPING = [
        742                       => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        763                       => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        780                       => [
            self::TCC_CODE        => 'T',
            self::TCC_DESCRIPTION => 'Mail Order/Telephone Order'
        ],
        1520                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        1711                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        1731                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        1740                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        1750                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        1761                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        1771                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        1799                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        2741                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        2791                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        2842                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        3000                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3001                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3002                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3003                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3004                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3005                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3006                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3007                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3008                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3009                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3010                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3011                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3012                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3013                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3014                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3015                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3016                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3017                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3018                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3020                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3021                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3022                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3023                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3024                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3025                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3026                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3027                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3028                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3029                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3030                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3031                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3032                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3033                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3034                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3035                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3036                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3037                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3038                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3039                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3040                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3041                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3042                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3043                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3044                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3045                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3046                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3047                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3048                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3049                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3050                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3051                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3052                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3053                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3054                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3055                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3056                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3057                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3058                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3059                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3060                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3061                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3062                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3063                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3064                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3065                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3066                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3067                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3068                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3069                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3071                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3072                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3075                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3076                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3077                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3078                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3079                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3082                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3083                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3084                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3085                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3087                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3088                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3089                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3090                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3094                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3096                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3097                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3098                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3099                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3100                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3102                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3103                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3106                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3111                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3112                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3117                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3125                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3127                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3129                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3130                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3131                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3132                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3136                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3144                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3146                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3148                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3151                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3156                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3159                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3161                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3164                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3167                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3171                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3172                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3174                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3175                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3177                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3178                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3180                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3181                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3182                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3183                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3184                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3185                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3186                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3187                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3188                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3190                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3191                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3193                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3196                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3197                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3200                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3204                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3206                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3211                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3212                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3213                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3217                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3219                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3220                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3221                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3222                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3223                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3226                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3228                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3229                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3231                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3234                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3236                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3239                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3240                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3241                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3242                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3243                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3245                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3246                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3247                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3248                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3252                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3253                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3256                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3260                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3261                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3263                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3266                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3267                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3280                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3282                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3285                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3286                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3287                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3292                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3293                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3294                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3295                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3296                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3297                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3298                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3299                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3300                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        3351                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3352                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3353                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3354                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3355                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3357                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3359                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3360                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3361                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3362                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3364                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3366                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3368                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3370                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3374                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3376                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3380                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3381                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3385                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3386                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3387                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3388                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3389                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3390                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3391                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3393                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3394                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3395                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3396                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3398                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3400                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3405                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3409                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3412                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3420                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3421                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3423                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3425                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3427                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3428                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3429                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3430                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3431                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3432                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3433                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3434                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3435                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3436                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3438                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3439                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3441                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        3501                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3502                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3503                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3504                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3505                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3506                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3507                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3508                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3509                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3510                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3511                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3512                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3513                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3514                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3515                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3516                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3517                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3518                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3519                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3520                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3521                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3522                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3523                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3524                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3525                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3526                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3527                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3528                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3529                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3530                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3531                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3532                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3533                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3534                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3535                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3536                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3537                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3538                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3539                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3540                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3541                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3542                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3543                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3544                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3545                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3546                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3547                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3548                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3549                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3550                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3551                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3552                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3553                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3554                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3555                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3556                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3557                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3558                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3559                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3560                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3561                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3562                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3563                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3564                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3565                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3566                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3567                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3568                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3569                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3570                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3571                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3572                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3573                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3574                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3575                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3576                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3577                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3578                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3579                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3580                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3581                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3582                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3583                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3584                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3585                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3586                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3587                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3588                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3589                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3590                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3591                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3592                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3593                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3594                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3595                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3596                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3597                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3598                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3599                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3600                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3601                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3602                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3603                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3604                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3605                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3606                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3607                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3608                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3609                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3610                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3611                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3612                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3613                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3614                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3615                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3616                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3617                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3618                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3619                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3620                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3621                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3622                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3623                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3624                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3625                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3626                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3627                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3628                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3629                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3630                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3631                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3632                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3633                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3634                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3635                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3636                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3637                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3638                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3639                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3640                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3641                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3642                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3643                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3644                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3645                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3646                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3647                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3648                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3649                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3650                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3651                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3652                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3653                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3654                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3655                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3656                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3657                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3658                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3659                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3660                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3661                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3662                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3663                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3664                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3665                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3666                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3667                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3668                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3669                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3670                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3671                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3672                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3673                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3674                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3675                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3676                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3677                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3678                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3679                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3680                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3681                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3682                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3683                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3684                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3685                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3686                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3687                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3688                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3689                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3690                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3691                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3692                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3693                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3694                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3695                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3696                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3697                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3698                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3699                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3700                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3701                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3702                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3703                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3704                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3705                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3706                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3707                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3708                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3709                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3710                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3711                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3712                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3713                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3714                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3715                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3716                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3717                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3718                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3719                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3720                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3721                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3722                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3723                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3724                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3725                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3726                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3727                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3728                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3729                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3730                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3731                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3732                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3733                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3734                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3735                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3736                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3737                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3738                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3739                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3740                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3741                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3742                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3743                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3744                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3745                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3746                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3747                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3748                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3749                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3750                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3751                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3752                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3753                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3754                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3755                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3757                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3758                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3759                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3760                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3761                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3762                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3763                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3764                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3765                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3766                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3767                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3768                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3769                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3770                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3771                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3772                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3773                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3774                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3775                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3776                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3777                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3778                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3779                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3780                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3781                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3782                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3783                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3784                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3785                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3786                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3787                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3788                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3789                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3790                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3791                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3792                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3793                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3794                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3795                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3796                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3797                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3798                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3799                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3800                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3801                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3802                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3803                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3804                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3805                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3806                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3807                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3808                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3809                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3810                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3811                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3812                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3813                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3814                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3815                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3816                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3817                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3818                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3819                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3820                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3821                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3823                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3824                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3825                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3826                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3827                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3828                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3829                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3830                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3831                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3832                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        3833                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        4011                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        4111                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        4112                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        4119                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        4121                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        4131                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        4214                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        4215                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        4225                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        4411                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel null'
        ],
        4457                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        4468                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        4511                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        4582                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        4722                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        4784                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        4789                      => [
            self::TCC_CODE        => 'X',
            self::TCC_DESCRIPTION => 'Airline'
        ],
        4812                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        4813                      => [
            self::TCC_CODE        => 'T',
            self::TCC_DESCRIPTION => 'Mail Order/Telephone Order'
        ],
        4814                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        4816                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        4821                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        4829                      => [
            self::TCC_CODE        => 'U',
            self::TCC_DESCRIPTION => 'Unique'
        ],
        4899                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        4900                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5013                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5021                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5039                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5044                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5045                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5046                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5047                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5051                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5065                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5072                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5074                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5085                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5094                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5099                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5111                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5122                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5131                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5137                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5139                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5169                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5172                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5192                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5193                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5198                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5199                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5200                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5211                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5231                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5251                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5261                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5271                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5300                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5309                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5310                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5311                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5331                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5399                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5411                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5422                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5441                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5451                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5462                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5499                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5511                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5521                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5531                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5532                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5533                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5541                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5542                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5551                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5561                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5571                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5592                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5598                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5599                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5611                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5621                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5631                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5641                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5651                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5655                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5661                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5681                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5691                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5697                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5698                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5699                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5712                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5713                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5714                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5718                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5719                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5722                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5732                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5733                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5734                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5735                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5811                      => [
            self::TCC_CODE        => 'F',
            self::TCC_DESCRIPTION => 'Food'
        ],
        5812                      => [
            self::TCC_CODE        => 'F',
            self::TCC_DESCRIPTION => 'Food'
        ],
        5813                      => [
            self::TCC_CODE        => 'F',
            self::TCC_DESCRIPTION => 'Food'
        ],
        5814                      => [
            self::TCC_CODE        => 'F',
            self::TCC_DESCRIPTION => 'Food'
        ],
        5815                      => [
            self::TCC_CODE        => 'T',
            self::TCC_DESCRIPTION => 'Mail Order/Telephone Order'
        ],
        5816                      => [
            self::TCC_CODE        => 'T',
            self::TCC_DESCRIPTION => 'Mail Order/Telephone Order'
        ],
        5817                      => [
            self::TCC_CODE        => 'T',
            self::TCC_DESCRIPTION => 'Mail Order/Telephone Order'
        ],
        5818                      => [
            self::TCC_CODE        => 'T',
            self::TCC_DESCRIPTION => 'Mail Order/Telephone Order'
        ],
        5912                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5921                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5931                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5932                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5933                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5935                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5937                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5940                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5941                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5942                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5943                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5944                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5945                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5946                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5947                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5948                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5949                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5950                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5960                      => [
            self::TCC_CODE        => 'T',
            self::TCC_DESCRIPTION => 'Mail Order/Telephone Order'
        ],
        5962                      => [
            self::TCC_CODE        => 'T',
            self::TCC_DESCRIPTION => 'Mail Order/Telephone Order'
        ],
        5963                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5964                      => [
            self::TCC_CODE        => 'T',
            self::TCC_DESCRIPTION => 'Mail Order/Telephone Order'
        ],
        5965                      => [
            self::TCC_CODE        => 'T',
            self::TCC_DESCRIPTION => 'Mail Order/Telephone Order'
        ],
        5966                      => [
            self::TCC_CODE        => 'T',
            self::TCC_DESCRIPTION => 'Mail Order/Telephone Order'
        ],
        5967                      => [
            self::TCC_CODE        => 'T',
            self::TCC_DESCRIPTION => 'Mail Order/Telephone Order'
        ],
        5968                      => [
            self::TCC_CODE        => 'T',
            self::TCC_DESCRIPTION => 'Mail Order/Telephone Order'
        ],
        5969                      => [
            self::TCC_CODE        => 'T',
            self::TCC_DESCRIPTION => 'Mail Order/Telephone Order'
        ],
        5970                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5971                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5972                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5973                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5975                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5976                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5977                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5978                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5983                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5992                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5993                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5994                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5995                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5996                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5997                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5998                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        5999                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        6010                      => [
            self::TCC_CODE        => 'C',
            self::TCC_DESCRIPTION => 'Cash Advance (Over the Counter)'
        ],
        6011                      => [
            self::TCC_CODE        => 'Z',
            self::TCC_DESCRIPTION => 'Cash Disbursement (ATM)'
        ],
        6012                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        6050                      => [
            self::TCC_CODE        => 'U',
            self::TCC_DESCRIPTION => 'Unique'
        ],
        6051                      => [
            self::TCC_CODE        => 'U',
            self::TCC_DESCRIPTION => 'Unique'
        ],
        6211                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        6300                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        6513                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        6538                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7011                      => [
            self::TCC_CODE        => 'H',
            self::TCC_DESCRIPTION => 'Hotel & Lodging'
        ],
        7012                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7032                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7033                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7210                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7211                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7216                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7217                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7221                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7230                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7251                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7261                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7273                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7276                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7277                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7278                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7296                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7297                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7298                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7299                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7311                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7321                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7333                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7338                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7339                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7342                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7349                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7361                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7372                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7375                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7379                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7392                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7393                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7394                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7395                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7399                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7512                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        7513                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        7519                      => [
            self::TCC_CODE        => 'A',
            self::TCC_DESCRIPTION => 'Automobile Rental'
        ],
        7523                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7531                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7534                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7535                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7538                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7542                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7549                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7622                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7623                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7629                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7631                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7641                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7692                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7699                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7800                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7801                      => [
            self::TCC_CODE        => 'U',
            self::TCC_DESCRIPTION => 'Unique'
        ],
        7802                      => [
            self::TCC_CODE        => 'U',
            self::TCC_DESCRIPTION => 'Unique'
        ],
        7829                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7832                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7841                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7911                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7922                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7929                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7932                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7933                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7941                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7991                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7992                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7993                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7994                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7995                      => [
            self::TCC_CODE        => 'U',
            self::TCC_DESCRIPTION => 'Unique'
        ],
        7996                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7997                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7998                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        7999                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8011                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8021                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8031                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8041                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8042                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8043                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8049                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8050                      => [
            self::TCC_CODE        => 'O',
            self::TCC_DESCRIPTION => 'Others'
        ],
        8062                      => [
            self::TCC_CODE        => 'O',
            self::TCC_DESCRIPTION => 'Others'
        ],
        8071                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8099                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8111                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8211                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8220                      => [
            self::TCC_CODE        => 'O',
            self::TCC_DESCRIPTION => 'Others'
        ],
        8241                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8244                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8249                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8299                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8351                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8398                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8641                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8651                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8661                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8675                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8699                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8734                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8911                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8931                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        8999                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        9211                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        9222                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        9223                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        9311                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        9399                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        9402                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
        9405                      => [
            self::TCC_CODE        => 'R',
            self::TCC_DESCRIPTION => 'Retail Sales'
        ],
    ];

    /**
     * @param int $mccCode
     *
     * @return array
     * @throws BadRequestException
     */
    public static function getTccFromMcc(int $mccCode): array
    {
        if (isset(self::MCC_TCC_MAPPING[$mccCode]))
        {
            return self::MCC_TCC_MAPPING[$mccCode];
        }

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_MERCHANT_INVALID_MCC_CODE);
    }
}
