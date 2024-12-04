# REVERSE_LC
Implements the user dashboard for REVERSE_LC REDCap projects!


## Participant Status Definitions

1. Screened
Criteria: [continue_yn] == 1 && [exclude_yn] != 1

2. Enrolled
Criteria: [randomization_dttm] != ''

3. Eligible
Criteria: [elig_app] == 1

4. Randomized
Criteria: [randomization] != ''

5. Consented
Criteria: [enroll_yn] == '1'



## Access Levels

authorized = '1'
- User can access dashboard
- User can see all sites data
- User cannot see my site data

authorized = '2'
- User can access dashboard
- User can see all sites data
- User can see my site data (Results limited to records with DAG that matches user's DAG)

authorized = '3'
- User can access dashboard
- User can see all sites data
- User can see my site data (Including all patient rows from all sites)