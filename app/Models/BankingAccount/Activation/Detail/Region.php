<?php

namespace RZP\Models\BankingAccount\Activation\Detail;


use Illuminate\Support\Str;

class Region
{
    const Andhra_Pradesh	 = 'Andhra Pradesh';
    const Arunachal_Pradesh  = 'Arunachal Pradesh';
    const Assam              = 'Assam';
    const Bihar              = 'Bihar';
    const Chhattisgarh       = 'Chhattisgarh';
    const Goa                = 'Goa';
    const Gujarat            = 'Gujarat';
    const Haryana            = 'Haryana';
    const Himachal_Pradesh   = 'Himachal Pradesh';
    const Jharkhand          = 'Jharkhand';
    const Karnataka          = 'Karnataka';
    const Kerala             = 'Kerala';
    const Madhya_Pradesh     = 'Madhya Pradesh';
    const Maharashtra        = 'Maharashtra';
    const Manipur            = 'Manipur';
    const Meghalaya          = 'Meghalaya';
    const Mizoram            = 'Mizoram';
    const Nagaland           = 'Nagaland';
    const Odisha             = 'Odisha';
    const Punjab             = 'Punjab';
    const Rajasthan          = 'Rajasthan';
    const Sikkim             = 'Sikkim';
    const Tamil_Nadu         = 'Tamil Nadu';
    const Telangana          = 'Telangana';
    const Tripura            = 'Tripura';
    const Uttar_Pradesh      = 'Uttar Pradesh';
    const Uttarakhand        = 'Uttarakhand';
    const West_Bengal        = 'West Bengal';
    const Andaman_And_Nicobar_Islands = 'Andaman and Nicobar Islands';
    const Chandigarh         = 'Chandigarh';
    const Dadra_And_Nagar_Haveli_And_Daman_and_Diu = 'Dadra & Nagar Haveli and Daman & Diu';
    const Delhi              = 'Delhi';
    const Jammu_And_Kashmir  = 'Jammu and Kashmir';
    const Lakshadweep        = 'Lakshadweep';
    const Puducherry         = 'Puducherry';
    const Ladakh             = 'Ladakh';

    const NORTH              = 'north';
    const EAST               = 'east';
    const WEST               = 'west';
    const SOUTH              = 'south';
    const CENTRAL            = 'central';

    protected static $stateToRegionMap = [
         self::Andhra_Pradesh	  => self::SOUTH,
         self::Arunachal_Pradesh  => self::NORTH,
         self::Assam              => self::EAST,
         self::Bihar              => self::NORTH,
         self::Chhattisgarh       => self::CENTRAL,
         self::Goa                => self::WEST,
         self::Gujarat            => self::WEST,
         self::Haryana            => self::NORTH,
         self::Himachal_Pradesh   => self::NORTH,
         self::Jharkhand          => self::EAST,
         self::Karnataka          => self::SOUTH,
         self::Kerala             => self::SOUTH,
         self::Madhya_Pradesh     => self::CENTRAL,
         self::Maharashtra        => self::WEST,
         self::Manipur            => self::EAST,
         self::Meghalaya          => self::EAST,
         self::Mizoram            => self::EAST,
         self::Nagaland           => self::EAST,
         self::Odisha             => self::EAST,
         self::Punjab             => self::NORTH,
         self::Rajasthan          => self::NORTH,
         self::Sikkim             => self::EAST,
         self::Tamil_Nadu         => self::SOUTH,
         self::Telangana          => self::SOUTH,
         self::Tripura            => self::SOUTH,
         self::Uttar_Pradesh      => self::NORTH,
         self::Uttarakhand        => self::NORTH,
         self::West_Bengal        => self::EAST,
         self::Andaman_And_Nicobar_Islands => self::SOUTH,
         self::Chandigarh         => self::NORTH,
         self::Dadra_And_Nagar_Haveli_And_Daman_and_Diu => self::WEST,
         self::Delhi              => self::NORTH,
         self::Jammu_And_Kashmir  => self::NORTH,
         self::Lakshadweep        => self::SOUTH,
         self::Puducherry         => self::SOUTH,
         self::Ladakh             => self::NORTH,
    ];

    public function getRegionFromState(string $state): string
    {
        return self::$stateToRegionMap[$state];
    }

}
