<?php

namespace RZP\Models\BankingAccount\Activation\Detail;


use Illuminate\Support\Str;

class Region
{
    const Andhra_Pradesh	 = 'andhra pradesh';
    const Arunachal_Pradesh  = 'arunachal pradesh';
    const Assam              = 'assam';
    const Bihar              = 'bihar';
    const Chhattisgarh       = 'chhattisgarh';
    const Chattisgarh        = 'chattisgarh';
    const Goa                = 'goa';
    const Gujarat            = 'gujarat';
    const Haryana            = 'haryana';
    const Himachal_Pradesh   = 'himachal pradesh';
    const Jharkhand          = 'jharkhand';
    const Karnataka          = 'karnataka';
    const Kerala             = 'kerala';
    const Madhya_Pradesh     = 'madhya pradesh';
    const Maharashtra        = 'maharashtra';
    const Manipur            = 'manipur';
    const Meghalaya          = 'meghalaya';
    const Mizoram            = 'mizoram';
    const Nagaland           = 'nagaland';
    const Odisha             = 'odisha';
    const Punjab             = 'punjab';
    const Rajasthan          = 'rajasthan';
    const Sikkim             = 'sikkim';
    const Tamil_Nadu         = 'tamil nadu';
    const TamilNadu          = 'tamilnadu';
    const Telangana          = 'telangana';
    const Tripura            = 'tripura';
    const Uttar_Pradesh      = 'uttar pradesh';
    const Uttarakhand        = 'uttarakhand';
    const West_Bengal        = 'west bengal';
    const Andaman_And_Nicobar_Islands = 'andaman and nicobar islands';
    const Chandigarh         = 'chandigarh';
    const Dadra_And_Nagar_Haveli_And_Daman_and_Diu = 'dadra & nagar haveli and daman & diu';
    const Delhi              = 'delhi';
    const Jammu_And_Kashmir  = 'jammukashmir';
    const Lakshadweep        = 'lakshadweep';
    const Puducherry         = 'puducherry';
    const Ladakh             = 'ladakh';

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
         self::Chattisgarh        => self::CENTRAL,
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
         self::TamilNadu          => self::SOUTH,
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
        // Convert state to lower before mapping since in some scenarios, pincode get API
        // returns state with in lower case instead of capitalised format.
        $stateLower = strtolower($state);

        return self::$stateToRegionMap[$stateLower];
    }

}
