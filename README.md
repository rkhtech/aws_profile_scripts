**contents should be placed in ~/bin/aws_profile_scripts**

**create symlink to /etc/bash_completion.d/**

    sudo ln -s /home/randy/bin/aws_profile_scripts/aws-profile_tab_completion /etc/bash_completion.d/aws-profile_tab_completion

add the following line to any of:   ~/.profile   ~/.bash_profile   ~/.bashrc

    source ~/bin/aws_profile_scripts/aws-profile_alias


Usage:

	aws-profile primary1
	aws-profile secondary
	aws-profile primary2


Example aws config files:

**~/.aws/config**
```
[default]
output = json
region = us-west-2

[profile rkhtech]
output = json
region = us-west-2

[profile secondary]
role_arn = arn:aws:iam::111111111111:role/AllowAdminAccess
source_profile = primary1
mfa_serial = arn:aws:iam::111111111111:mfa/randy
output = json
region = us-west-2
```
Note: Account number 111111111111 should be the account number on the primary1 account, and the role should have already been created.  
Reference: https://aws.amazon.com/blogs/security/how-to-enable-cross-account-access-to-the-aws-management-console/

**~/.aws/credentials**

```
[default]
aws_access_key_id = AKIAI5XOALWNZFUC4PAA
aws_secret_access_key = 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
[primary1]
aws_access_key_id = AKIAI5XOALWNZFUC4PAA
aws_secret_access_key = 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
[primary2]
aws_access_key_id = AKIAIPBDA5RVPXF4S3SQ
aws_secret_access_key = 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
```

(Don't worry, these access keys were deleted before this file was pushed to git)