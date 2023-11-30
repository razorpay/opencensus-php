<?php

namespace RZP\Models\Gateway\File;

/**
 * List of metrics for SubscriptionRegistration
 */
final class Metric
{
    const EMANDATE_FILE_GENERATED                 = 'emandate_file_generated';
    
    const EMANDATE_FILE_GENERATION_ERROR          = 'emandate_file_generation_error';
    
    const EMANDATE_DATA_ENTITY_ERROR              = 'emandate_data_entity_error';
    
    const EMANDATE_DB_QUERY_COMPLETE            = 'emandate_db_query_complete';
    
    const EMANDATE_DB_ERROR                       = 'emandate_db_error';
    
    const EMANDATE_BEAM_ERROR                     = 'emandate_beam_error';
    
    const EMANDATE_FILE_SENT                      = 'emandate_file_sent';
    
    const EMANDATE_FILE_SENT_ERROR                = 'emandate_file_sent_error';
}
