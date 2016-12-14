#Setup Instructions:
### Download repo
```
git clone git@github.com:rkhtech/aws_profile_scripts.git ~/bin/aws_profile_scripts
```
* Contents should be placed in ~/bin/aws_profile_scripts

### create symlink to /etc/bash_completion.d/

    sudo ln -s ~/bin/aws_profile_scripts/aws-profile_tab_completion /etc/bash_completion.d/aws-profile_tab_completion

### add the following line(s) to any of:   `~/.profile   ~/.bash_profile   ~/.bashrc`

```bash
export AWS_DEFAULT_PROFILE=$(cat ~/.aws/current_profile)
source ~/bin/aws_profile_scripts/aws-profile_alias

#optionally: reconfigure your PS1 value to include:
export PS1="[AWS:\$AWS_DEFAULT_PROFILE] \$ "
```

# Usage:

```bash
[AWS:default] $ aws-profile primary1
[AWS:primary1] $ aws-profile secondary
[AWS:secondary] $ aws-profile primary2
[AWS:primary2] $ 
```


##Example AWS config files:

### ~/.aws/config
```
[default]
output = json
region = us-west-2

[profile primary1]
output = json
region = us-west-2

[profile primary2]
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
Reference: [How to Enable Cross-Account Access to the AWS Management Console](https://aws.amazon.com/blogs/security/how-to-enable-cross-account-access-to-the-aws-management-console/)

### ~/.aws/credentials

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

(Don't worry, these access keys were deleted before this file was pushed to github)
