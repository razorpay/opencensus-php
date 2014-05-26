<?php 

namespace Models\DAL;


class Hash extends DAL
{
    protected $fillable = array(
        'hash'
    );

    protected $primaryKey = 'hash';

    protected $table  = 'hashes';
}