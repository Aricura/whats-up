# ~/.bash_profile: executed by bash(1) for login shells.
# see /usr/share/doc/bash/examples/startup-files (in the package bash-doc)
# for examples

# If not running interactively, don't do anything
case $- in
    *i*) ;;
      *) return;;
esac

# The various escape codes that we can use to color our prompt.
        RED='\[\e[31;1m\]'
     YELLOW='\[\e[33;1m\]'
      GREEN='\[\e[32;1m\]'
       BLUE='\[\e[34;1m\]'
      WHITE='\[\e[0;1m\]'
       GRAY='\[\e[37;1m\]'
       CYAN='\[\e[36;1m\]'
     VIOLET='\[\e[35;1m\]'
 COLOR_NONE='\[\e[0m\]'

# Return the prompt symbol to use, colorized based on the return value of the
# previous command.
function set_prompt_symbol () {
    if test $1 -eq 0 ; then
      PROMPT_COLOR="${WHITE}"
    else
      PROMPT_COLOR="${RED}"
    fi
}

function set_bash_prompt () {
    # Set the PROMPT_SYMBOL variable. We do this first so we don't lose the
    # return value of the last command.
    set_prompt_symbol $?

    # set variable identifying the chroot you work in (used in the prompt below)
    if [ -z "${debian_chroot:-}" ] && [ -r /etc/debian_chroot ]; then
        debian_chroot=$(cat /etc/debian_chroot)
    fi

    # set a fancy prompt (non-color, unless we know we "want" color)
    case "$TERM" in
        xterm-color) color_prompt=yes;;
    esac

    # uncomment for a colored prompt, if the terminal has the capability; turned
    # off by default to not distract the user: the focus in a terminal window
    # should be on the output of commands, not on the prompt
    force_color_prompt=yes

    if [ -n "$force_color_prompt" ]; then
        if [ -x /usr/bin/tput ] && tput setaf 1 >&/dev/null; then
            # We have color support; assume it's compliant with Ecma-48
            # (ISO/IEC-6429). (Lack of such support is extremely rare, and such
            # a case would tend to support setf rather than setaf.)
            color_prompt=yes
        else
            color_prompt=
        fi
    fi

    if [ "$color_prompt" = yes ]; then
        PS1="${PROMPT_COLOR}┌─( ${RED}${debian_chroot:+($debian_chroot)}\u${YELLOW}@${CYAN}\h${PROMPT_COLOR} ) - ( ${YELLOW}\w${PROMPT_COLOR} )\n${PROMPT_COLOR}└──┤ ${COLOR_NONE}"
        PS2="${PROMPT_COLOR}   │ ${COLOR_NONE}"
    else
        PS1="┌─( ${debian_chroot:+($debian_chroot)}\u@\h ) - ( \w )\n└──┤ "
        PS2="   │ "
    fi
    unset color_prompt force_color_prompt

    # If this is an xterm set the title to user@host:dir
    case "$TERM" in
    xterm*|rxvt*)
        PS1="\[\e]0;${debian_chroot:+($debian_chroot)}\u@\h: \w\a\]$PS1"
        ;;
    *)
        ;;
    esac
}

# Tell bash to execute this function just before displaying its prompt.
PROMPT_COMMAND=set_bash_prompt

# temporary history location, because filesystem is ro
HISTFILE=/tmp/.bash_history

# don't put duplicate lines or lines starting with space in the history.
# See bash(1) for more options
HISTCONTROL=ignoreboth

# append to the history file, don't overwrite it
shopt -s histappend

# for setting history length see HISTSIZE and HISTFILESIZE in bash(1)
HISTSIZE=10000
HISTFILESIZE=

# check the window size after each command and, if necessary,
# update the values of LINES and COLUMNS.
shopt -s checkwinsize

# If set, the pattern "**" used in a pathname expansion context will
# match all files and zero or more directories and subdirectories.
shopt -s globstar

# make less more friendly for non-text input files, see lesspipe(1)
[ -x /usr/bin/lesspipe ] && eval "$(SHELL=/bin/sh lesspipe)"

# Alias definitions.
# You may want to put all your additions into a separate file like
# ~/.bash_aliases, instead of adding them here directly.
# See /usr/share/doc/bash-doc/examples in the bash-doc package.
if [ -f ~/.bash_aliases ]; then
    . ~/.bash_aliases
fi

# enable programmable completion features (you don't need to enable
# this, if it's already enabled in /etc/bash.bashrc and /etc/profile
# sources /etc/bash.bashrc).
if ! shopt -oq posix; then
    if [ -f /usr/share/bash-completion/bash_completion ]; then
        . /usr/share/bash-completion/bash_completion
    elif [ -f /etc/bash_completion ]; then
        . /etc/bash_completion
    fi
fi
