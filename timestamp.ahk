^!t::
{
    CurrentDateTime := FormatTime(A_Now, "HH_mm_ss-dd_MMM_yyyy_ddd")
    SendText(CurrentDateTime)
}
