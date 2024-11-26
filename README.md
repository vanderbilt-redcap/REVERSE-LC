# REVERSE_LC
Implements the user dashboard for REVERSE_LC REDCap projects!


Participant Status Definitions

Variables and Definitions
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
