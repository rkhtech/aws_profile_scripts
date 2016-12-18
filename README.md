#What is this for?
My initial desire for creating this was to quickly change profiles when using the AWS CLI tools.  Before this tool I would have to use the `--profile` command repeately, and then I would forget which account I needed to change or I would use the wrong `--profile` or simply forget to use the option and end up executing an AWS command in the wrong account.  This was bad.

So below in the Usage I've explained how easy it is to switch profiles, and I've also modified my prompt to show me what AWS account I'm currently working with.  

## Features:
* It creates an alias to a function that will change the environment variable `AWS_DEFAULT_PROFILE`
* It scans the entire contents of your `~/.aws/config` and `~/.aws/credentials` to find all valid configuration options.  It will use this list to configure tab autocompletion helping the use of changing profiles.  
* It also saves what your current profile is, so that next time a bash shell is created, you are exactly where you left off.
* According to [Best Practices for Managing AWS Access Keys](http://docs.aws.amazon.com/general/latest/gr/aws-access-keys-best-practices.html#iam-user-access-keys) it is best to rotate your access keys periodically.  
    * This repo also includes an alias for making that easy:
    * `aws-rotate-access-keys`
    
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
export PS1="\033[38;5;214m[AWS:\$AWS_DEFAULT_PROFILE] \$ "
```

# Usage:

```bash
[AWS:default] $ aws-profile primary1
[AWS:primary1] $ aws-profile secondary
[AWS:secondary] $ aws-profile primary2
[AWS:primary2] $ aws-rotate-access-keys
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
